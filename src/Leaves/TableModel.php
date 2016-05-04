<?php

namespace Rhubarb\Leaf\Table\Leaves;

use Rhubarb\Crown\Events\Event;
use Rhubarb\Leaf\Leaves\LeafModel;
use Rhubarb\Leaf\Table\Leaves\Columns\TableColumn;
use Rhubarb\Leaf\Table\Leaves\FooterProviders\FooterColumn;
use Rhubarb\Stem\Collections\Collection;

class TableModel extends LeafModel
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
     * @var FooterColumn[]  An array of footers to present
     */
    public $footerProviders = [];

    /**
     * @var string[]    An array of css class names for the table
     */
    public $tableCssClassNames = [];

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
    public $getAdditionalClientSideRowData;

    public function __construct()
    {
        parent::__construct();

        $this->getRowCssClassesEvent = new Event();
        $this->getAdditionalClientSideRowData = new Event();
    }

    /**
     * Return the list of properties that can be exposed publically
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