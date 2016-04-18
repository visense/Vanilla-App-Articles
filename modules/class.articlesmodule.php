<?php
/**
 * Articles module
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Renders recently posted articles
 */
class ArticlesModule extends Gdn_Module {
    /** @var int Display limit. */
    public $Limit = 5;

    /** @var string */
    public $Prefix = 'Discussion';

    /** @var array Limit the discussions to just this list of categories, checked for view permission. */
    protected $CategoryIDs;

    /**
     * Initializes class
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

        // Let DiscussionModel know that the sender is the ArticlesModule.
        // Used by discussionModel_beforeGet_handler() method to only
        // show non-article discussions when viewing ArticleController.
        $discussionModel->Module = 'ArticlesModule';

        $categoryIDs = $this->getCategoryIDs();
        $where = array(
            'Announce' => 'all',
            'd.Type' => 'Article'
        );

        if ($categoryIDs) {
            $where['d.CategoryID'] = CategoryModel::filterCategoryPermissions($categoryIDs);
        } else {
            $discussionModel->Watching = false;
        }

        $this->fireEvent('ArticlesModuleBeforeGet');

        $this->setData('Discussions', $discussionModel->get(0, $limit, $where));
    }

    /**
     * Defines asset to display module in.
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Renders the module.
     *
     * @return string
     */
    public function toString() {
        if (!$this->data('Discussions')) {
            $this->getData();
        }

        return ($this->data('Discussions')->numRows() > 0) ? parent::toString() : null;
    }

    /**
     * Get a list of category IDs to limit.
     *
     * @return array
     */
    public function getCategoryIDs() {
        return $this->CategoryIDs;
    }

    /**
     * Set a list of category IDs to limit.
     *
     * @param array $categoryIDs
     */
    public function setCategoryIDs($categoryIDs) {
        $this->CategoryIDs = $categoryIDs;
    }
}
