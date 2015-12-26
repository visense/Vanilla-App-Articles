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

    /** @var boolean Value indicating if article options should be displayed when rendering the article view. */
    public $ShowOptions;

    /**
     * Default all articles view: sorted by most recent article
     *
     * @param int $page Multiplied by PerPage option to determine offset.
     */
    public function index($page = false) {
        // Setup head
        if (!$this->data('Title')) {
            $title = c('Garden.HomepageTitle');
            $defaultControllerRoute = val('Destination', Gdn::router()->GetRoute('DefaultController'));
            if ($title && ($defaultControllerRoute == 'articles')) {
                $this->title($title, '');
            } else {
                $this->title(t('Recent Articles'));
            }
        }

        // Add modules
        saveToConfig('Vanilla.DefaultNewButton', 'post/article', array('Save' => false));
        $this->addModule('NewDiscussionModule');
        $this->addModule('DiscussionFilterModule');
        $this->addModule('CategoriesModule');
        $this->addModule('BookmarkedModule');

        // Determine offset from $page
        list($offset, $limit) = offsetLimit($page, c('Articles.Articles.PerPage', 12), true);
        $page = pageNumber($offset, $limit);

        // Allow page manipulation
        $this->EventArguments['Page'] = &$page;
        $this->EventArguments['Offset'] = &$offset;
        $this->EventArguments['Limit'] = &$limit;
        $this->fireEvent('AfterPageCalculation');

        // Set canonical URL
        $this->canonicalUrl(url(ConcatSep('/', 'articles', pageNumber($offset, $limit, true, false)), true));

        // Get articles
        $wheres = array('d.Type' => 'Article');
        $this->DiscussionModel->Watching = true; // Only show categories with permission to view
        $this->setData('CountDiscussions', $this->DiscussionModel->getCount($wheres));
        $this->setData('Discussions', $this->DiscussionModel->getWhere($wheres, $offset, $limit), true);
        $this->ShowOptions = true;

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'articles/{Page}');
        }
        $this->Pager = $PagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure($offset, $limit, $this->data('CountDiscussions'), $this->data('_PagerUrl'));

        PagerModule::current($this->Pager);

        $this->setData('_Page', $page);
        $this->setData('_Limit', $limit);
        $this->fireEvent('AfterBuildPager');

        // Render
        $this->setData('Breadcrumbs', array(array('Name' => t('Recent Articles'), 'Url' => '/articles')));
        $this->render();
    }
}