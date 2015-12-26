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
    public $Uses = array('ArticleModel', 'DiscussionModel');

    /**
     * Default single article view
     */
    public function index($year, $urlCode, $page = '') {
        // Get discussion with article UrlCode
        $discussion = $this->ArticleModel->getByUrlCode($urlCode);

        if (!$discussion) {
            throw notFoundException('Article');
        }

        $this->setData('Discussion', $discussion, true);

        //

        // Render
        $this->render();
    }
}