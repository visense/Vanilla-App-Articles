<?php
/**
 * Discussions module
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Renders recently active discussions
 */
class ArticlesModule extends Gdn_Module {

    /** @var int Display limit. */
    public $Limit = 5;

    /** @var string  */
    public $Prefix = 'Discussion';

    /** @var array Limit the discussions to just this list of categories, checked for view permission. */
    protected $categoryIDs;

    /**
     *
     *
     * @throws Exception
     */
    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'articles';
        $this->fireEvent('Init');
    }

    /**
     * Get the data for the module.
     *
     * @param int|bool $limit Override the number of discussions to display.
     */
    public function getData($limit = false) {
        if (!$limit) {
            $limit = $this->Limit;
        }

        $discussionModel = new DiscussionModel();

        // Let DiscussionModel know that the sender is the ArticlesModule
        // Used by discussionModel_beforeGet_handler() method to only show non-article discussions when viewing ArticleController
        $discussionModel->Module = 'ArticlesModule';

        $categoryIDs = $this->getCategoryIDs();
        $where = array(
            'Announce' => 'all',
            'd.Type' => 'Article'
        );

        if ($categoryIDs) {
            $where['d.CategoryID'] = CategoryModel::filterCategoryPermissions($categoryIDs);
        } else {
            $discussionModel->Watching = true;
        }

        $this->fireEvent('ArticlesModuleBeforeGet');

        $this->setData('Discussions', $discussionModel->get(0, $limit, $where));
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        if (!$this->data('Discussions')) {
            $this->GetData();
        }

        return parent::ToString();
    }

    /**
     * Get a list of category IDs to limit.
     *
     * @return array
     */
    public function getCategoryIDs() {
        return $this->categoryIDs;
    }

    /**
     * Set a list of category IDs to limit.
     *
     * @param array $categoryIDs
     */
    public function setCategoryIDs($categoryIDs) {
        $this->categoryIDs = $categoryIDs;
    }
}
