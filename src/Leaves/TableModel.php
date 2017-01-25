<?php

namespace Rhubarb\Leaf\Table\Leaves;

use Rhubarb\Crown\Events\Event;
use Rhubarb\Leaf\Leaves\UrlStateLeafModel;
use Rhubarb\Leaf\Table\Leaves\Columns\TableColumn;
use Rhubarb\Leaf\Table\Leaves\FooterProviders\FooterProvider;
use Rhubarb\Stem\Collections\Collection;

class TableModel extends UrlStateLeafModel
{
    /**
     * @var Collection The stem collection being presented.
     */
    public $collection;

    /**
     * @var string The HTML to show instead of the table in the event that there are no rows to display.
     */
    public $noDataHtml;

    /**
     * @var string The HTML to show instead of the table before a search has been performed.
     */
    public $unsearchedHtml;

    /**
     * @var string Ability to set if you want to display pager at bottom.
     */
    public $repeatPagerAtBottom;

    /**
     * @var TableColumn[] The columns to present
     */
    public $columns;

    /**
     * @var string[] A dictionary of column names for export.
     */
    public $exportColumns;

    /**
     * @var int The number of rows in each page
     */
    public $pageSize;

    /**
     * @var FooterProvider[]  An array of FooterProviders to provide footer content
     */
    public $footerProviders = [];

    /**
     * @var string The name of the column being used for sorting
     */
    public $sortColumn;

    /**
     * @var bool The direction of the sort (true = ASC, false = DESC)
     */
    public $sortDirection;

    /**
     * @var bool True if the table is being refreshed because of a search.
     */
    public $searched = false;

    /**
     * @var Event Raised when the view needs class names for a row.
     */
    public $getRowCssClassesEvent;

    /**
     * @var Event Raised when the view needs additional row data for data- attributes
     */
    public $getAdditionalClientSideRowDataEvent;

    /**
     * @var Event Raised when the user clicks a column heading
     */
    public $columnClickedEvent;

    /**
     * @var Event
     */
    public $pageChangedEvent;

    /**
     * @var Event
     */
    public $pagerUrlStateNameChangedEvent;

    /**
     * @var Event
     */
    public $collectionUpdatedEvent;

    /**
     * @var string The name of the GET param which will provide state for this table in the URL
     * If you have multiple tables on a page and want URL state to apply to them all independently, you'll need to make this unique.
     * Set it to null to disable URL state for this table.
     */
    public $urlStateName = 'sort';

    public function __construct()
    {
        parent::__construct();

        $this->getRowCssClassesEvent = new Event();
        $this->getAdditionalClientSideRowDataEvent = new Event();
        $this->columnClickedEvent = new Event();
        $this->pageChangedEvent = new Event();
        $this->pagerUrlStateNameChangedEvent = new Event();
        $this->collectionUpdatedEvent = new Event();

        $this->collectionUpdatedEvent->attachHandler(function (Collection $collection) {
            $this->collection = $collection;
        });
    }

    /**
     * Return the list of properties that can be exposed publicly
     *
     * @return array
     */
    protected function getExposableModelProperties()
    {
        $list = parent::getExposableModelProperties();

        $list[] = "sortColumn";
        $list[] = "sortDirection";
        $list[] = "searched";

        return $list;
    }
}
