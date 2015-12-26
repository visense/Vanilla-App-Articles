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

        if (strtolower(val('Type', $discussion)) === 'article') {
            echo ' <span class="Tag Article-Tag">' . T("Article") . '</span> ';
        }
    }

//    public function discussionModel_beforeGet_handler($sender, $args) {
//        // Hide discussions with article type from indexes.
//        if (!isset($args['Wheres']['d.Type'])) {
//            $sender->SQL->where('d.Type <>', 'Article');
//            $sender->SQL->orWhere('d.Type', null);
//        }
//    }

    public function discussionModel_setCalculatedFields_handler($sender, $args) {
        $discussion = $args['Discussion'];

        // If discussion is of type 'Article'
        if (strtolower(val('Type', $discussion)) === 'article') {
            // Join discussion with article data
            $discussionID = val('DiscussionID', $discussion);
            $articleModel = new ArticleModel();
            $article = $articleModel->getByDiscussionID($discussionID)->firstRow();

            if ($article) {
                setValue('Article', $discussion, $article);
            }

            // Change URL of discussion to article
            // Must be called after discussion has been joined with article data (to retrieve UrlCode)
            $discussion->Url = articleUrl($discussion);
        }
    }

    public function postController_render_before($sender) {
        if (strtolower($sender->RequestMethod) === 'editdiscussion'
            && strtolower($sender->data('Type')) === 'article'
            && $sender->View !== 'preview'
        ) {
            $sender->View = PATH_APPLICATIONS . '/articles/views/post/article.php';
            $sender->title(t('Edit Article'));
        }
    }

    // Redirect /discussion to article if type of article

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
            'AddText' => 'Compose an Article'
        );
    }

    /**
     * Add the article form to Vanilla's post page.
     *
     * @param PostController $sender Event sender.
     */
    public function postController_afterForms_handler($sender) {
        $forms = $sender->Data('Forms');
        $forms[] = array('Name' => 'Article', 'Label' => Sprite('SpArticle') . T('Compose an Article'), 'Url' => 'post/article');
        $sender->setData('Forms', $forms);
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

    public function discussionModel_beforeSaveDiscussion_handler($sender, $args) {
        $formPostValues = &$args['FormPostValues'];
    }

    /**
     * Override the PostController->Discussion() method before render to use our view instead.
     *
     * @param PostController $sender Event sender.
     */
    public function postController_beforeDiscussionRender_handler($sender) {
        // Override if we are looking at the article URL
        if ($sender->RequestMethod === 'article') {
            $sender->Form->addHidden('Type', 'Article');
            $sender->title(T('Compose an Article'));
            $sender->setData('Breadcrumbs', array(array('Name' => $sender->Data('Title'), 'Url' => '/post/article')));
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