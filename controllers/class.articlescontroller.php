<?php
/**
 * Articles controller
 *
 * @copyright 2015 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Handles displaying articles in most contexts via /articles endpoint.
 */
class ArticlesController extends VanillaController {
    /** @var arrayModels to include. */
    public $Uses = array('DiscussionModel');

    public function index() {
        ini_set('display_errors',1);
        ini_set('display_startup_errors',1);
        error_reporting(E_ALL ^ E_STRICT);

        $this->ClassName = 'DiscussionsController';

        // stuff
        // Get Discussions
        $this->DiscussionData = $this->DiscussionModel->getWhere(array(), 0, 0);
        $this->CountCommentsPerPage = c('Vanilla.Comments.PerPage', 30);
        $this->ShowOptions = true;

        // render
        $this->View = $this->fetchViewLocation('discussions', 'Discussions', 'vanilla', false);

        $this->render();
    }
}