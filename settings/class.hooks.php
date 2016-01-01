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
    public function base_render_before($Sender) {
        if ($Sender->Menu) {
            $Sender->Menu->AddLink('Articles', T('Articles'), '/articles', 'Articles.Articles.View');
        }
    }

    /**
     * Show article tag in discussion indexes.
     *
     * @param Gdn_Controller $sender The controller.
     * @param array $args Event arguments.
     */
    public function base_beforeDiscussionMeta_handler($sender, $args) {
        $discussion = $args['Discussion'];

        if (ArticleModel::isArticle($discussion)) {
            echo ' <span class="Tag Article-Tag">' . T("Article") . '</span> ';
        }
    }

    public function discussionModel_beforeGet_handler($sender, $args) {
//        // Hide discussions with article type from indexes.
//        if (!isset($args['Wheres']['d.Type'])) {
//            $sender->SQL->where('d.Type <>', 'Article');
//            $sender->SQL->orWhere('d.Type', null);
//        }

        // Always show latest article on top in ArticlesController
        if (strtolower(Gdn::controller()->ControllerName) === 'articlescontroller') {
            $args['SortField'] = 'd.DateInserted';
            $args['SortDirection'] = 'desc';
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
        echo '<li class="Articles' . (strtolower($sender->ControllerName) == 'articlescontroller'
            && strtolower($sender->RequestMethod) == 'index' ? ' Active' : '') . '">'
            . anchor(sprite('SpArticles') . ' ' . t('Articles'), '/articles') . '</li>';
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
                $sender->Form->addHidden('ArticleUrlCodeIsDefined', '1');
            } else {
                $sender->Form->addHidden('ArticleUrlCodeIsDefined', '0');
            }
        }
    }

    public function postController_render_before($sender) {
        $editingArticle = strtolower($sender->RequestMethod) === 'editdiscussion'
            && strtolower($sender->data('Type')) === 'article'
            && $sender->View !== 'preview';

        // Add CSS and JS assets to article methods
        if (strtolower($sender->RequestMethod) === 'article' || $editingArticle) {
            $sender->addCssFile('post.css', 'articles');
            $sender->addJsFile('post.js', 'articles');
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

    public function discussionModel_beforeSaveDiscussion_handler($sender, $args) {
        // Do validation of inputs before saving discussion
        $formPostValues = &$args['FormPostValues'];

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
    }

    public function discussionModel_afterSaveDiscussion_handler($sender, $args) {
        $discussionID = $args['DiscussionID'];
        $discussion = $args['Discussion'];
        $formPostValues = $args['FormPostValues'];

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
        $articleModel->save($fields);
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