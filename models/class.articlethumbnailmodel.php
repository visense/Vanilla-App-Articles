<?php
/**
 * ArticleThumbnail model
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Handles data for articles.
 */
class ArticleThumbnailModel extends Gdn_Model {
    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('ArticleThumbnail');
    }

    /**
     * Get thumbnail by article ID.
     *
     * @param int $articleID
     * @return bool|object
     */
    public function getByArticleID($articleID) {
        // Set up the query.
        $thumbnail = $this->SQL->select('at.*')
            ->from('ArticleThumbnail at')
            ->where('at.ArticleID', $articleID)
            ->get()->firstRow();

        return $thumbnail;
    }

    /**
     * Handles deletion of an article thumbnail from file system and database.
     *
     * @param object|int $thumbnail ArticleThumbnail entity or ArticleThumbnailID
     * @return Gdn_Dataset
     */
    public function delete($thumbnail) {
        if (is_numeric($thumbnail)) {
            $thumbnail = $this->getID($thumbnail);
        }

        // Delete image file
        $imagePath = PATH_UPLOADS . DS . $thumbnail->Path;
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }

        // Delete thumbnail entry from database
        parent::delete($thumbnail->ArticleThumbnailID);
    }

    /**
     * Handles deletion of an article thumbnail by an article ID.
     *
     * @param int $articleID
     */
    public function deleteByArticleID($articleID) {
        if (!is_numeric($articleID)) {
            throw new InvalidArgumentException('The article ID must be a numeric value.');
        }

        // Call delete method after retrieving thumbnail by article ID
        $this->delete($this->getByArticleID($articleID));
    }
}
