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

    public function discussionModel_setCalculatedFields_handler($sender, &$args) {
        $discussion = &$args['Discussion'];

        if (strtolower(val('Type', $discussion)) === 'article') {
            $discussion->Url = articleUrl($discussion);
        }
    }

    public function postController_render_before($sender) {
        if (strtolower($sender->RequestMethod) === 'editdiscussion'
                && strtolower($sender->data('Type')) === 'article'
                && $sender->View !== 'preview') {
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

    /**
     * Run any setup code that a plugin requires before it is ready for general use.
     *
     * This method will be called every time a plugin is enabled,
     * so it should check before performing redundant operations like
     * inserting tables or data into the database. If a plugin has no setup to
     * perform, simply declare this method and return TRUE.
     *
     * Returns a boolean value indicating success.
     *
     * @return boolean
     */
    public function setup() {
        return true;
    }
}