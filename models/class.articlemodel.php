<?php
/**
 * Article model
 *
 * @copyright 2015 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Handles data for articles.
 */
class ArticleModel extends Gdn_Model {
    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('Article');
    }

    public function getByDiscussionID($discussionID) {
        if (!is_numeric($discussionID))
            throw new InvalidArgumentException('The discussion ID must be a numeric value.');

        return $this->getWhere(array('DiscussionID' => $discussionID));
    }

    public function getByUrlCode($urlCode) {
        $this->SQL->select('a.DiscussionID')
            ->From('Article a')
            ->Where('a.UrlCode', $urlCode);

        // Fetch data.
        $article = $this->SQL->get()->firstRow();

        if ($article) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($article->DiscussionID);

            return $discussion;
        }

        return false;
    }
}
