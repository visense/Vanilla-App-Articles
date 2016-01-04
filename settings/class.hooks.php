<?php
/**
 * ArticlesHooks Plugin
 *
 * @copyright 2015 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Articles' event handlers.
 */
class ArticlesHooks implements Gdn_IPlugin {
    /**
     * Add link to the articles controller in the main menu.
     *
     * @param Gdn_Controller $Sender
     */
    public function base_render_before($sender) {
        if ($sender->Menu && c('Articles.ShowArticlesMenuLink')) {
            $sender->Menu->addLink('Articles', T('Articles'), '/articles');
        }
    }

    /**
     * Show article tag in discussion indexes.
     *
     * @param Gdn_Controller $sender The controller.
     * @param array $args Event arguments.
     */
    public function base_beforeDiscussionMeta_handler($sender, $args) {
        if (strtolower($sender->ControllerName) !== 'articlescontroller') {
            $discussion = $args['Discussion'];

            if (ArticleModel::isArticle($discussion)) {
                echo ' <span class="Tag Article-Tag">' . T("Article") . '</span> ';
            }
        }
    }

    public function discussionController_discussionOptions_handler($sender, $args) {
        if (ArticleModel::isArticle($args['Discussion'])) {
            $args['DiscussionOptions']['DeleteDiscussion']['Label'] = t('Delete Article');
        }
    }

    public function discussionController_render_before($sender) {
        if (strtolower($sender->RequestMethod) === 'delete'
                && ArticleModel::isArticle($sender->DiscussionModel->EventArguments['Discussion'])
        ) {
            // Override delete discussion page translations
            $sender->title(t('Delete Article'));

            Gdn::locale()->setTranslation('Are you sure you want to delete this %s?', sprintf(t('Are you sure you want to delete this %s?'), t('article')), false);
        } else if (strtolower($sender->RequestMethod) === 'index'
                && ArticleModel::isArticle($sender->data('Discussion'))) {
            // DiscussionController->Index() method for discussion of type article
        } else {
            $sender->addModule('ArticlesModule');
        }
    }

    public function discussionsModule_init_handler($sender) {
        $controllerName = strtolower(Gdn::controller()->ControllerName);

        //if ($controllerName === 'articlescontroller') {
        $discussionModel = new DiscussionModel();

        // Let DiscussionModel know that the sender is the DiscussionsModule
        // Used by discussionModel_beforeGet_handler() method to only show non-article discussions when viewing ArticlesController
        $discussionModel->Module = 'DiscussionsModule';

        $categoryIDs = $sender->getCategoryIDs();
        $where = array('Announce' => 'all');

        if ($categoryIDs) {
            $where['d.CategoryID'] = CategoryModel::filterCategoryPermissions($categoryIDs);
        } else {
            $discussionModel->Watching = true;
        }

        $sender->setData('Discussions', $discussionModel->get(0, $sender->Limit, $where));
        //}
    }

    public function discussionModel_beforeGet_handler($sender, $args) {
//        // Hide discussions with article type from indexes.
//        if (!isset($args['Wheres']['d.Type'])) {
//            $sender->SQL->where('d.Type <>', 'Article');
//            $sender->SQL->orWhere('d.Type', null);
//        }

        $controller = Gdn::controller();
        $controllerName = strtolower($controller->ControllerName);

        $senderIsDiscussionsModule = isset($sender->Module) && $sender->Module === 'DiscussionsModule';
        $senderIsArticlesModule = isset($sender->Module) && $sender->Module === 'ArticlesModule';

        // Always show latest article on top in ArticlesController
        if (($controllerName === 'articlescontroller' && !$senderIsDiscussionsModule)
                || ($controllerName === 'discussioncontroller' && $senderIsArticlesModule)) {
            $args['SortField'] = 'd.DateInserted';
            $args['SortDirection'] = 'desc';
        }

        // Only show discussions of type article in Recent Articles module
        if ($controllerName === 'articlescontroller' && $senderIsDiscussionsModule) {
            $sender->SQL->where('d.Type', 'Article');
            $sender->SQL->Where('d.CountComments >', 0);
            $sender->SQL->orWhere('d.Type', null);
        } else if ($controllerName === 'discussioncontroller' && $senderIsArticlesModule) {
            $discussion = $controller->EventArguments['Discussion'];

            if (ArticleModel::isArticle($discussion)) {
                $sender->SQL->where('d.DiscussionID <>', $discussion->DiscussionID);
            }
        }
    }

    public function discussionModel_setCalculatedFields_handler($sender, $args) {
        $discussion = &$args['Discussion'];

        // If discussion is of type 'Article'
        if (ArticleModel::isArticle($discussion)) {
            // Join discussion with article data if not already joined
            if (!val('ArticleID', $discussion, false)) {
                ArticleModel::joinArticle($discussion, val('DiscussionID', $discussion));
            }

            // Change URL of discussion to article
            // Must be called after discussion has been joined with article data (to retrieve UrlCode)
            $discussion->Url = articleUrl($discussion);
        }

        //
    }

    public function discussionModel_deleteDiscussion_handler($sender, $args) {
        if (ArticleModel::isArticle($args['Discussion'])) {
            $discussionID = $args['DiscussionID'];

            $articleModel = new ArticleModel();
            $article = $articleModel->getByDiscussionID($discussionID);

            // Delete article thumbnail
            $articleThumbnailModel = new ArticleThumbnailModel();
            $articleThumbnailModel->deleteByArticleID($article->ArticleID);

            // Delete article
            $articleModel->delete($article->ArticleID);
        }
    }

//    public function discussionModel_AfterAddColumns_handler($sender, $args) {
//        // Join article data
//        $data = &$args['Data'];
//        if ($data instanceof Gdn_DataSet) {
//            $data2 = $data->result();
//        } else {
//            $data2 = &$data;
//        }
//
//        foreach ($data2 as &$discussion) {
//            if (strtolower(val('Type', $discussion)) === 'article' && !val('ArticleID', $discussion, false)) {
//                ArticleModel::joinArticle($discussion, val('DiscussionID', $discussion));
//            }
//        }
//    }


    public function discussionController_beforeDiscussionRender_handler($sender) {
        $discussion = $sender->data('Discussion');

        if (ArticleModel::isArticle($discussion)) {
            // Redirect discussion to article controller
            redirect(articleUrl($discussion));
        }
    }

    /**
     * Add the article discussion type.
     *
     * @param Gdn_PluginManager $sender Event sender.
     * @param array $args Event arguments.
     */
    public function base_discussionTypes_handler($sender, &$args) {
        $args['Types']['Article'] = array(
            'Singular' => 'Article',
            'Plural' => 'Articles',
            'AddUrl' => '/post/article',
            'AddText' => 'Compose Article'
        );
    }

    /**
     * Add the article form to Vanilla's post page.
     *
     * @param PostController $sender Event sender.
     */
    public function postController_afterForms_handler($sender) {
        $forms = $sender->Data('Forms');
        $forms[] = array('Name' => 'Article', 'Label' => Sprite('SpArticle') . T('Compose Article'), 'Url' => 'post/article');
        $sender->setData('Forms', $forms);
    }

    public function base_beforeDiscussionFilters_handler($sender) {
        if (c('Articles.ShowArticlesMenuLink')) {
            echo '<li class="Articles' . (strtolower($sender->ControllerName) == 'articlescontroller'
                && strtolower($sender->RequestMethod) == 'index' ? ' Active' : '') . '">'
                . anchor(sprite('SpArticles') . ' ' . t('Articles'), '/articles') . '</li>';
        }
    }

    /**
     * Create the new article method on post controller.
     *
     * @param PostController $sender Event sender.
     * @param string $categoryUrlCode URL code for current category.
     */
    public function postController_article_create($sender, $categoryUrlCode = '') {
        // Create & call PostController->Discussion()
        $sender->View = PATH_APPLICATIONS . '/articles/views/post/article.php';
        $sender->setData('Type', 'Article');
        $sender->discussion($categoryUrlCode);
    }

    /**
     * Override the PostController->Discussion() method before render to use our view instead.
     *
     * @param PostController $sender Event sender.
     */
    public function postController_beforeDiscussionRender_handler($sender) {
        // Override if we are looking at the article URL
        $editingArticle = strtolower($sender->RequestMethod) === 'editdiscussion'
            && ArticleModel::isArticle($sender->data('Discussion'))
            && $sender->View !== 'preview';

        if ($sender->RequestMethod === 'article' || $editingArticle) {
            $sender->Form->addHidden('Type', 'Article');
            $sender->title($editingArticle ? T('Edit Article') : T('Compose Article'));

            if ($editingArticle) {
                // If editing
                $articleID = $sender->data('Discussion')->ArticleID;
                $sender->Form->addHidden('ArticleID', $articleID);

                // Add URL code identifier
                $sender->Form->addHidden('ArticleUrlCodeDefined', '1');

                // Set author based on InsertUserID
                $authorName = Gdn::userModel()->getID($sender->Data['Discussion']->InsertUserID)->Name;
                $sender->Form->setValue('ArticleAuthorName', $authorName);

                // Get thumbnail
                $articleThumbnailModel = new ArticleThumbnailModel();
                $thumbnail = $articleThumbnailModel->getByArticleID($articleID);
                if ($thumbnail) {
                    $sender->setData('ArticleThumbnail', $thumbnail, true);
                }
            } else {
                // If not editing
                $sender->Form->addHidden('ArticleUrlCodeDefined', '0');

                // Set default author
                $authorName = Gdn::session()->User->Name;
                $sender->Form->setValue('ArticleAuthorName', $authorName);
            }
        }
    }

    public function postController_render_before($sender) {
        $editingArticle = strtolower($sender->RequestMethod) === 'editdiscussion'
            && strtolower($sender->data('Type')) === 'article'
            && $sender->View !== 'preview';

        // Add CSS and JS assets to article methods
        if (strtolower($sender->RequestMethod) === 'article' || $editingArticle) {
            $sender->AddJsFile('jquery.autocomplete.js');

            $sender->addCssFile('post.css', 'articles');
            $sender->addJsFile('post.js', 'articles');
            $sender->addJsFile('jquery.ajaxfileupload.js', 'articles');
        }

        // Override editdiscussion view
        if ($editingArticle) {
            $sender->View = PATH_APPLICATIONS . '/articles/views/post/article.php';
            $sender->title(t('Edit Article'));

            // Override editdiscussion breadcrumb link
            $breadcrumbs = $sender->Data('Breadcrumbs');

            if (isset($breadcrumbs[count($breadcrumbs)]) && $breadcrumbs[count($breadcrumbs)]['Url'] === '/post/discussion') {
                $breadcrumbs[count($breadcrumbs)]['Url'] = '/post/article';
            }

            $sender->setData('Breadcrumbs', $breadcrumbs);
        }
    }

    /**
     * Allows the user to upload an image to an article via AJAX.
     *
     * @return false on failure
     * @throws NotFoundException if no files posted
     * @throws PermissionException if user doesn't have permission to upload
     */
    public function postController_uploadArticleThumbnail_create($sender) {
        // Check for file data
        if (!$_FILES) {
            throw NotFoundException('Page');
        }

        // Check permission
        $sender->permission('Vanilla.Discussions.Add');

        // Handle the file data
        $sender->deliveryMethod(DELIVERY_METHOD_JSON);
        $sender->deliveryType(DELIVERY_TYPE_VIEW);

        // ArticleID is saved with media model if editing. ArticleID is null if new article.
        // Null ArticleID is replaced by ArticleID when new article is saved.
        $articleID = $_POST['ArticleID'];
        if (!is_numeric($articleID) || ($articleID <= 0)) {
            $articleID = null;
        }

        /*
         * $_FILES['UploadImage_New'] array format:
         *  'name' => 'example.jpg',
         *  'type' => 'image/jpeg',
         *  'tmp_name' => 'C:\example\tmp\example.tmp' (temp. path on the user's computer to .tmp file)
         *  'error' => 0 (valid data)
         *  'size' => 15517 (bytes)
         */
        //$imageData = $_FILES[$UploadFieldName];
        $uploadFieldName = 'ArticleThumbnail_New';

        // Upload the image.
        $uploadImage = new Gdn_UploadImage();
        try {
            $tmpFileName = $uploadImage->validateUpload($uploadFieldName);

            // Generate the target image name.
            $currentYear = date('Y');
            $currentMonth = date('m');
            $uploadPath = PATH_UPLOADS . '/articles/' . $currentYear . '/' . $currentMonth;
            $targetImage = $uploadImage->generateTargetName($uploadPath, false, false);
            $basename = pathinfo($targetImage, PATHINFO_BASENAME);
            $extension = trim(pathinfo($targetImage, PATHINFO_EXTENSION), '.');
            $uploadsSubdir = '/articles/' . $currentYear . '/' . $currentMonth;

            $saveWidth = c('Articles.Articles.ThumbnailWidth', 260);
            $saveHeight = c('Articles.Articles.ThumbnailHeight', 146);

            // Save the uploaded image.
            $props = $uploadImage->saveImageAs(
                $tmpFileName,
                $uploadsSubdir . '/' . $basename,
                $saveHeight,
                $saveWidth,
                array('OutputType' => $extension, 'ImageQuality' => c('Garden.UploadImage.Quality', 75))
            );

            $uploadedImagePath = sprintf($props['SaveFormat'], $uploadsSubdir . '/' . $basename);
        } catch (Exception $ex) {
            return false;
        }

        // Save the image
        $imageProps = getimagesize($targetImage);

        $articleThumbnailModel = new ArticleThumbnailModel();

        $thumbnailValues = array(
            'ArticleID' => $articleID,
            'Name' => $basename,
            'Type' => $imageProps['mime'],
            'Size' => filesize($targetImage),
            'ImageWidth' => $imageProps[0],
            'ImageHeight' => $imageProps[1],
            'Path' => $uploadedImagePath,
            'DateInserted' => Gdn_Format::toDateTime(),
            'InsertUserID' => Gdn::session()->UserID,
        );

        $articleThumbnailID = $articleThumbnailModel->save($thumbnailValues);

        // Return path to the uploaded image in the following format.
        // Example: '/articles/year/month/filename.jpg'
        $jsonData = array(
            'ArticleThumbnailID' => $articleThumbnailID,
            'Name' => $basename,
            'Path' => $uploadedImagePath
        );

        $jsonReturn = json_encode($jsonData);

        die($jsonReturn);
    }

    /**
     * Allows the user to delete an image from an article.
     *
     * @param int $articleThumbnailID
     * @throws NotFoundException if invalid articleThumbnailID
     * @throws PermissionException if user doesn't have permission to upload
     */
    public function postController_deleteArticleThumbnail_create($sender, $articleThumbnailID) {
        if (!is_numeric($articleThumbnailID) || $sender->deliveryMethod() != DELIVERY_METHOD_JSON || $sender->deliveryType() != DELIVERY_TYPE_BOOL) {
            throw notFoundException('Page');
        }

        // Check permission
        $sender->permission('Vanilla.Discussions.Add');

        $articleThumbnailModel = new ArticleThumbnailModel();
        $thumbnail = $articleThumbnailModel->getID($articleThumbnailID);
        if (!$thumbnail) {
            throw notFoundException('Article thumbnail');
        }

        $sender->deliveryMethod(DELIVERY_METHOD_JSON);
        $sender->deliveryType(DELIVERY_TYPE_BOOL);

        // Delete the thumbnail
        $articleThumbnailModel->delete($thumbnail);

        $sender->render('Blank', 'Utility', 'Dashboard');
    }

    public function discussionModel_beforeSaveDiscussion_handler($sender, $args) {
        // Do validation of inputs before saving discussion
        $formPostValues = &$args['FormPostValues'];

        // If not article, then exit this method
        if (!isset($formPostValues['Type']) || strtolower($formPostValues['Type']) !== 'article') {
            return;
        }

        // Set validation rules, such as required inputs
        $sender->Validation->applyRule('ArticleUrlCode', 'Required', 'URL code is required.');

        // Check if URL code is unique
        $urlCode = Gdn_Format::url($formPostValues['ArticleUrlCode']);
        if (strlen($urlCode) > 0) {
            $articleModel = new ArticleModel();
            $article = $articleModel->getByUrlCode($urlCode);

            // If URL code exists in the table, and if editing a discussion, is not attached to the discussion ID
            if ($article && (!isset($formPostValues['DiscussionID']) || ($article->DiscussionID !== (int)$formPostValues['DiscussionID']))) {
                $sender->Validation->addValidationResult('ArticleUrlCode', 'That URL code is in use by another article. It must be unique.');
            }
        }

        // Check if the user in author name field exists
        // If the author name is the same as the current user's name, then the author definitely exists
        if (isset($formPostValues['ArticleAuthorName']) && strcasecmp($formPostValues['ArticleAuthorName'], Gdn::session()->User->Name) !== 0) {
            $authorName = $formPostValues['ArticleAuthorName'];
            $author = Gdn::userModel()->getByUsername($authorName);

            if (!$author) {
                $sender->Validation->addValidationResult('ArticleAuthorName', 'The user name entered for the author does not exist.');
            }
        }
    }

    public function discussionModel_afterSaveDiscussion_handler($sender, $args) {
        // Gather variables
        $discussion = &$args['Discussion'];

        // If not article, then exit this method
        if (!ArticleModel::isArticle($discussion)) {
            return;
        }

        $discussionID = $args['DiscussionID'];
        $formPostValues = $args['FormPostValues'];

        // Update discussion InsertUserID if author changed
        if (isset($formPostValues['ArticleAuthorName'])) {
            $authorName = $formPostValues['ArticleAuthorName'];

            // Author definitely exists since the field is validated in the BeforeSaveDiscussion event
            $author = Gdn::userModel()->getByUsername($authorName);

            // If author doesn't exist, the current InsertUserID is kept
            // Or if the author is the same as the current InsertUserID, don't do unnecessary checking.
            if ($author && $author->UserID !== $discussion->InsertUserID) {
                $oldInsertUserID = $discussion->InsertUserID;

                $discussionModel = new DiscussionModel();

                // Update discussion InsertUserID with new author's UserID
                $discussionModel->update(
                    array('InsertUserID' => $author->UserID),
                    array('DiscussionID' => $discussionID)
                );

                $discussion->InsertUserID = $author->UserID;

                // Update old and new authors' discussion counts
                $discussionModel->updateUserDiscussionCount($oldInsertUserID);
                $discussionModel->updateUserDiscussionCount($author->UserID, true); // Increment

                // Yaga application support. With Yaga, users can't react to their own posts.
                // Make sure the discussion doesn't have a reaction by the new author.
                // This code could be turned into an event and run within the Yaga application itself,
                // but for now let's put it here.
                if (Gdn::applicationManager()->isEnabled('Yaga')) {
                    $reactionModel = Yaga::ReactionModel();

                    // Get an object of the reaction the user has made
                    $reaction = $reactionModel->GetByUser($discussionID, 'discussion', $author->UserID);

                    if (is_object($reaction)) {
                        // the ReactionModel->Set() method removes the reaction for a discussion if the user already has a reaction for the action ID
                        $reactionModel->Set($discussionID, 'discussion', $oldInsertUserID, $author->UserID, $reaction->ActionID);
                    }
                }
            }
        }

        // Create or update (save) article entity for discussion
        $articleModel = new ArticleModel();
        $fields = array();

        // Check for existing article
        // Depends on article data being joined via discussionModel_setCalculatedFields_handler
        $articleID = val('ArticleID', $discussion, false);
        if ($articleID) {
            $fields['ArticleID'] = $articleID;
        }

        // Attach article properties to fields to be saved
        $fields['DiscussionID'] = $discussionID;
        $fields['UrlCode'] = Gdn_Format::url($formPostValues['ArticleUrlCode']);
        $fields['Excerpt'] = (strlen($formPostValues['ArticleExcerpt']) > 0) ? $formPostValues['ArticleExcerpt'] : null;

        // Save the article
        $articleID = $articleModel->save($fields);

        // Set article ID to thumbnail ID
        $articleThumbnailID = (int)$formPostValues['ArticleThumbnailID'];
        if ($articleThumbnailID > 0) {
            $articleThumbnailModel = new ArticleThumbnailModel();
            $articleThumbnailModel->setField($articleThumbnailID, 'ArticleID', $articleID);
        }
    }

    public function structure() {
        // Call structure.php to update database
        include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'structure.php');
    }

    /**
     * Automatically executed when application is enabled.
     */
    public function setup() {
        $this->structure();

        // Set home page
        saveToConfig('Routes.DefaultController', 'articles');

        // Set current Articles version
        $ApplicationInfo = array();
        include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'about.php');
        $version = arrayValue('Version', arrayValue('Articles', $ApplicationInfo, array()), 'Undefined');
        saveToConfig('Articles.Version', $version);
    }
}