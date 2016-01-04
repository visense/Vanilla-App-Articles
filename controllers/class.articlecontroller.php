<?php
/**
 * Article controller
 *
 * @copyright 2015 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Handles displaying an article in most contexts via /article endpoint.
 */
class ArticleController extends VanillaController {
    /** @var arrayModels to include. */
    public $Uses = array('ArticleModel', 'ArticleThumbnailModel', 'DiscussionModel', 'CommentModel', 'Form');

    /** @var array Unique identifier. */
    public $CategoryID;

    /**
     * Default single article view
     */
    public function index($year, $urlCode, $page = '') {
        // Setup head
        $session = Gdn::session();
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('autosave.js', 'vanilla');
        $this->addJsFile('discussion.js', 'vanilla');
        Gdn_Theme::section('Discussion');

        // Load the discussion record
        if (!array_key_exists('Discussion', $this->Data)) {
            $this->setData('Discussion', $this->ArticleModel->getByUrlCode($urlCode), true);
        }

        if (!is_object($this->Discussion) || $year !== date('Y', strtotime($this->Discussion->DateInserted))) {
            throw notFoundException('Article');
        }

        // Add CSS
        $this->addCssFile('articles.css');

        if (gdn::themeManager()->currentTheme() === 'mobile') {
            $this->addCssFile('articles.mobile.css');
        }

        $this->DiscussionID = $this->Discussion->DiscussionID;

        // Define the query offset & limit.
        $limit = c('Vanilla.Comments.PerPage', 30);

        $offsetProvided = $page != '';
        list($offset, $limit) = offsetLimit($page, $limit);

        // Check permissions
        $this->permission('Vanilla.Discussions.View', true, 'Category', $this->Discussion->PermissionCategoryID);
        $this->setData('CategoryID', $this->CategoryID = $this->Discussion->CategoryID, true);

        if (strcasecmp(val('Type', $this->Discussion), 'redirect') === 0) {
            $this->redirectDiscussion($this->Discussion);
        }

        $category = CategoryModel::categories($this->Discussion->CategoryID);
        $this->setData('Category', $category, true);

        if ($categoryCssClass = val('CssClass', $category)) {
            Gdn_Theme::section($categoryCssClass);
        }

        $this->setData('Breadcrumbs', CategoryModel::getAncestors($this->CategoryID));

        // Setup
        $this->title($this->Discussion->Name);

        // Actual number of comments, excluding the discussion itself.
        $ActualResponses = $this->Discussion->CountComments;

        $this->Offset = $offset;
        if (c('Vanilla.Comments.AutoOffset')) {
            if (!is_numeric($this->Offset) || $this->Offset < 0 || !$offsetProvided) {
                // Round down to the appropriate offset based on the user's read comments & comments per page
                $CountCommentWatch = $this->Discussion->CountCommentWatch > 0 ? $this->Discussion->CountCommentWatch : 0;
                if ($CountCommentWatch > $ActualResponses) {
                    $CountCommentWatch = $ActualResponses;
                }

                // (((67 comments / 10 perpage) = 6.7) rounded down = 6) * 10 perpage = offset 60;
                $this->Offset = floor($CountCommentWatch / $limit) * $limit;
            }
            if ($ActualResponses <= $limit) {
                $this->Offset = 0;
            }

            if ($this->Offset == $ActualResponses) {
                $this->Offset -= $limit;
            }
        } else {
            if ($this->Offset == '') {
                $this->Offset = 0;
            }
        }

        if ($this->Offset < 0) {
            $this->Offset = 0;
        }


        $latestItem = $this->Discussion->CountCommentWatch;
        if ($latestItem === null) {
            $latestItem = 0;
        } elseif ($latestItem < $this->Discussion->CountComments) {
            $latestItem += 1;
        }

        $this->setData('_LatestItem', $latestItem);

        // Set the canonical url to have the proper page title.
        $this->canonicalUrl(articleUrl($this->Discussion, pageNumber($this->Offset, $limit, 0, false)));

        // Load the comments
        $this->setData('Comments', $this->CommentModel->get($this->DiscussionID, $limit, $this->Offset));

        $pageNumber = PageNumber($this->Offset, $limit);
        $this->setData('Page', $pageNumber);

        // Set meta tags
        $this->addMetaTags();

        if ($pageNumber > 1) {
            $this->Data['Title'] .= sprintf(t(' - Page %s'), PageNumber($this->Offset, $limit));
        }

        // Queue notification.
        if ($this->Request->get('new') && c('Vanilla.QueueNotifications')) {
            $this->addDefinition('NotifyNewDiscussion', 1);
        }

        // Make sure to set the user's discussion watch records
        $this->CommentModel->SetWatch($this->Discussion, $limit, $this->Offset, $this->Discussion->CountComments);

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        $this->Pager = $pagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';

        $this->Pager->configure(
            $this->Offset,
            $limit,
            $ActualResponses,
            array('DiscussionUrl')
        );
        $this->Pager->Record = $this->Discussion;
        PagerModule::current($this->Pager);
        $this->fireEvent('AfterBuildPager');

        // Define the form for the comment input
        $this->Form = Gdn::Factory('Form', 'Comment');
        $this->Form->Action = url('/post/comment/');
        $this->DiscussionID = $this->Discussion->DiscussionID;
        $this->Form->addHidden('DiscussionID', $this->DiscussionID);
        $this->Form->addHidden('CommentID', '');

        // Look in the session stash for a comment
        $stashComment = $session->getPublicStash('CommentForDiscussionID_' . $this->Discussion->DiscussionID);
        if ($stashComment) {
            $this->Form->setFormValue('Body', $stashComment);
        }

        // Retrieve & apply the draft if there is one:
        if ($session->UserID) {
            $DraftModel = new DraftModel();
            $Draft = $DraftModel->get($session->UserID, 0, 1, $this->Discussion->DiscussionID)->firstRow();
            $this->Form->addHidden('DraftID', $Draft ? $Draft->DraftID : '');
            if ($Draft && !$this->Form->isPostBack()) {
                $this->Form->setValue('Body', $Draft->Body);
                $this->Form->setValue('Format', $Draft->Format);
            }
        }

        // Deliver JSON data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            $this->View = $this->fetchViewLocation('comments', 'discussion', 'vanilla');
        }

        // Inform moderator of checked comments in this discussion
        $CheckedComments = $session->getAttribute('CheckedComments', array());
        if (count($CheckedComments) > 0) {
            ModerationController::informCheckedComments($this);
        }

        // Add modules
        saveToConfig('Vanilla.DefaultNewButton', 'post/article', array('Save' => false));
        $this->addModule('NewDiscussionModule');
        $this->addModule('DiscussionFilterModule');
        $this->addModule('CategoriesModule');
        $this->addModule('BookmarkedModule');
        $this->addModule('ArticlesModule');
        $this->addModule('RecentActivityModule');

        $this->CanEditComments = $session->checkPermission('Vanilla.Comments.Edit', true, 'Category', 'any') && c('Vanilla.AdminCheckboxes.Use');

        // Report the discussion id so js can use it.
        $this->addDefinition('DiscussionID', $this->DiscussionID);
        $this->addDefinition('Category', $this->data('Category.Name'));

        $this->fireEvent('BeforeDiscussionRender');

        $attachmentModel = AttachmentModel::instance();
        if (AttachmentModel::enabled()) {
            $attachmentModel->joinAttachments($this->Data['Discussion'], $this->Data['Comments']);

            $this->fireEvent('FetchAttachmentViews');
            if ($this->deliveryMethod() === DELIVERY_METHOD_XHTML) {
                require_once $this->fetchViewLocation('attachment', 'attachments', 'dashboard');
            }
        }

        $this->View = $this->fetchViewLocation('index', 'article');

        // Mimic the DiscussionController so addons can run event code
        $this->ClassName = 'DiscussionController';
        $this->ControllerName = 'discussioncontroller';

        $this->render();
    }

    protected function addMetaTags() {
        if (!$this->Head) {
            return;
        }

        $this->Head->addTag('meta', array('property' => 'og:type', 'content' => 'article'));

        // Date published
        $this->Head->addTag('meta', array('property' => 'article:published_time',
            'content' => date(DATE_ISO8601, strtotime($this->Discussion->DateInserted))));

        // Date modified
        if (!is_null($this->Discussion->DateUpdated)) {
            $this->Head->addTag('meta', array('property' => 'article:modified_time',
                'content' => date(DATE_ISO8601, strtotime($this->Discussion->DateUpdated))));
        }

        // Set description
        if (strlen($this->Discussion->ArticleExcerpt) > 0) {
            $description = Gdn_Format::plainText($this->Discussion->ArticleExcerpt, $this->Discussion->Format);
        } else {
            $description = sliceParagraph(Gdn_Format::plainText($this->Discussion->Body, $this->Discussion->Format), 160);
        }

        $this->description($description);

        // Author info
        $user = Gdn::userModel()->getID($this->Discussion->InsertUserID);
        $this->Head->addTag('meta', array('property' => 'article:author', 'content' => url(userUrl($user), true)));

        // Category
        $this->Head->addTag('meta', array('property' => 'article:section', 'content' => val('Name', $this->Category)));

        // Thumbnail
        $thumbnail = $this->ArticleThumbnailModel->getByArticleID($this->Discussion->ArticleID);
        if ($thumbnail) {
            $thumbnailPath = url('/uploads' . $thumbnail->Path, true);

            $this->image($thumbnailPath);
        }

        // Add images from discussion body to head for open graph
        include_once(PATH_LIBRARY . '/vendors/simplehtmldom/simple_html_dom.php');
        $dom = str_get_html(Gdn_Format::to($this->Discussion->Body, $this->Discussion->Format));
        if ($dom) {
            foreach ($dom->find('img') as $img) {
                if (isset($img->src)) {
                    $this->image($img->src);
                }
            }
        }

        // Twitter card
        $this->Head->addTag('meta', array('name' => 'twitter:card', 'content' => 'summary'));

        $twitterUsername = trim(c('Articles.TwitterUsername', ''));
        if ($twitterUsername != '') {
            $this->Head->addTag('meta', array('name' => 'twitter:site', 'content' => '@' . $twitterUsername));
        }

        $this->Head->addTag('meta', array('name' => 'twitter:title', 'content' => $this->Discussion->Name));
        $this->Head->addTag('meta', array('name' => 'twitter:description', 'content' => $description));

        if ($thumbnail) {
            $this->Head->addTag('meta', array('name' => 'twitter:image', 'content' => $thumbnailPath));
        }
    }
}