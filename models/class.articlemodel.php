<?php
/**
 * Article model
 *
 * @copyright 2015-2016 Austin S.
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

    /**
     * Merges article data with a discussion object.
     *
     * @param object $discussion
     * @param int $discussionID
     */
    public static function joinArticle(&$discussion, $discussionID) {
        $articleModel = new ArticleModel();

        $article = $articleModel->getByDiscussionID($discussionID);

        if ($article) {
            $discussion = (object)array_merge((array)$discussion, (array)$article);
        }
    }

    /**
     * Checks if a discussion object has a type of 'article'.
     *
     * @param array|object $discussion
     * @return bool
     */
    public static function isArticle($discussion) {
        // Convenience function
        // Check if discussion object or array has type of Article
        return (strtolower(val('Type', $discussion, false)) === 'article');
    }

    /**
     * Get only an article entity by a discussion ID.
     *
     * @param int $discussionID
     * @return array|bool|stdClass Article entity
     * @throws Exception if discussion ID isn't numeric
     */
    public function getByDiscussionID($discussionID) {
        if (!is_numeric($discussionID)) {
            throw new InvalidArgumentException('The discussion ID must be a numeric value.');
        }

        $this->SQL->select('a.ArticleID')
            ->select('a.UrlCode', '', 'ArticleUrlCode')
            ->select('a.Excerpt', '', 'ArticleExcerpt')
            ->from('Article a')
            ->where('a.DiscussionID', $discussionID)
            ->limit(1);

        return $this->SQL->get()->firstRow();
    }

    /**
     * Get a discussion via the unique URL code identifier of an article.
     *
     * @param $urlCode
     * @return bool|object Discussion entity with article data joined or false if article doesn't exist
     * @throws Exception
     */
    public function getByUrlCode($urlCode) {
        if (!is_string($urlCode) || strlen($urlCode) === 0) {
            return false;
        }

        $this->SQL->select('a.DiscussionID')
            ->from('Article a')
            ->where('a.UrlCode', $urlCode)
            ->limit(1);

        // Fetch data.
        $article = $this->SQL->get()->firstRow();

        if ($article) {
            // Grab discussion that has article data joined via the SetCalculatedFields event
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($article->DiscussionID);

            return $discussion;
        }

        return false;
    }
}
