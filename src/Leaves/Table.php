<?php

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\Leaf\Table\Leaves;

use Rhubarb\Crown\Application;
use Rhubarb\Crown\DataStreams\CsvStream;
use Rhubarb\Crown\Events\Event;
use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\FileResponse;
use Rhubarb\Crown\String\StringTools;
use Rhubarb\Leaf\Leaves\BindableLeafTrait;
use Rhubarb\Leaf\Leaves\Leaf;
use Rhubarb\Leaf\Leaves\UrlStateLeaf;
use Rhubarb\Leaf\Table\Leaves\Columns\ClosureColumn;
use Rhubarb\Leaf\Table\Leaves\Columns\LeafColumn;
use Rhubarb\Leaf\Table\Leaves\Columns\ModelColumn;
use Rhubarb\Leaf\Table\Leaves\Columns\SortableColumn;
use Rhubarb\Leaf\Table\Leaves\Columns\TableColumn;
use Rhubarb\Leaf\Table\Leaves\Columns\Template;
use Rhubarb\Leaf\Table\Leaves\FooterProviders\FooterProvider;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Decorators\DataDecorator;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlForeignKeyColumn;
use Rhubarb\Stem\Schema\Relationships\OneToOne;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * Presents an HTML table using a ModelList as it's data source
 */
class Table extends UrlStateLeaf
{
    /**
     * @var TableModel
     */
    protected $model;

    /**
     * A collection of column names or TableColumn objects
     *
     * @var string[]|TableColumn[]
     */
    public $columns = [];

    /**
     * @var Event An event that gives hosting leaves an opportunity to return a Filter object used to filter the
     *            table's collection.
     */
    public $getFilterEvent;

    /**
     * @var Event An opportunity for a host to return specific classes for a particular row
     *            It will be raised with arguments for the $model and the $rowNumber.
     */
    public $getRowCssClassesEvent;

    /**
     * @var Event An opportunity for a host to provide additional data for data-* attributes on the table rows
     *            It will be raised with arguments for the $model and the $rowNumber.
     */
    public $getAdditionalClientSideRowDataEvent;

    /**
     * @var Event An opportunity for other Leaf objects to listen for updates on the Table
     */
    public $collectionModifiedEvent;

    /**
     * @var Model
     */
    private $currentRow;

    public function __construct(Collection $list = null, $pageSize = 50, $presenterName = "Table")
    {
        $this->collectionModifiedEvent = new Event();

        parent::__construct($presenterName, function (TableModel $model) use ($list, $pageSize) {
            $model->collection = $list;
            $model->originalCollection = clone $list;
            $model->pageSize = $pageSize;
        });

        $this->getFilterEvent = new Event();
    }

    public function setUrlStateNames($pagerName = "page", $sortName = "sort")
    {
        $this->model->pagerUrlStateNameChangedEvent->raise($pagerName);
        $this->model->urlStateName = $sortName;
    }

    public function setExportColumns($columns)
    {
        $this->model->exportColumns = $columns;
    }

    protected function createModel()
    {
        return new TableModel();
    }

    protected function onModelCreated()
    {
        parent::onModelCreated();

        $this->model->columnClickedEvent->attachHandler(function ($index) {
            // Get the inflated columns so we know which one we're dealing with.
            $columns = $this->inflateColumns($this->columns);
            $column = $columns[$index];

            if ($column instanceof SortableColumn) {
                // Change the sort order.
                $this->changeSort($column->getSortableColumnName());
            }

            return $index;
        });

        $this->model->pageChangedEvent->attachHandler(function () {
            $this->reRender();
        });

        // Pass through for events
        $this->getRowCssClassesEvent = $this->model->getRowCssClassesEvent;
        $this->getAdditionalClientSideRowDataEvent = $this->model->getAdditionalClientSideRowDataEvent;
    }

    public function addFooter(FooterProvider $provider)
    {
        $provider->setTable($this);

        $this->model->footerProviders[] = $provider;
    }

    public function clearFooters()
    {
        $this->model->footerProviders = [];
    }

    public function getCollection()
    {
        return clone $this->model->collection;
    }

    public function exportList()
    {
        $this->configureFilters();

        $cachePath = TEMP_DIR . "/cache/";

        if (file_exists($cachePath . "export.csv")) {
            unlink($cachePath . "export.csv");
        }

        $file = $cachePath . "export.csv";

        $stream = new CsvStream($file);

        $columns = $this->inflateColumns($this->model->exportColumns);
        $headings = [];

        foreach ($columns as $column) {
            $headings[] = $column->label;
        }

        $stream->setHeaders(
            $headings
        );

        $this->model->collection->disableRanging();

        foreach ($this->model->collection as $item) {
            $data = [];

            $decorator = DataDecorator::getDecoratorForModel($item);

            if (!$decorator) {
                $decorator = $item;
            }

            foreach ($columns as $column) {
                $data[$column->label] = $column->getCellContent($item, $decorator);
            }

            $stream->appendItem($data);
        }

        // Push this file to the browser.
        throw new ForceResponseException(new FileResponse($file));
    }

    public function setCollection($collection)
    {
        $this->model->collectionUpdatedEvent->raise($collection);
        $this->model->originalCollection = clone $collection;
    }

    protected function changeSort($columnName)
    {
        $currentDirection = false;

        if ($this->model->sortColumn == $columnName) {
            $currentDirection = ($this->model->sortDirection) ? $this->model->sortDirection : false;
        }

        $currentDirection = !$currentDirection;

        $this->model->sortColumn = $columnName;
        $this->model->sortDirection = $currentDirection;

        $this->reRender();
    }

    protected function applySort()
    {
        if ($this->model->sortColumn) {
            $this->model->collection->replaceSort($this->model->sortColumn, $this->model->sortDirection);
        }
    }

    private $schemaColumns = false;

    private function getSchemaColumns()
    {
        // Make sure the collection has been fetched otherwise pull up schema details won't be
        // available.
        $this->model->collection->count();

        if (!$this->schemaColumns) {
            $schema = $this->model->collection->getModelSchema();
            $this->schemaColumns = [];
            $columns = $schema->getColumns();

            foreach($columns as $column){
                $storageColumns = $column->getStorageColumns();
                $this->schemaColumns = array_merge($this->schemaColumns, $storageColumns);
            }

            // Loop over pull up column details and consider them too.
            foreach ($this->model->collection->additionalColumns as $alias => $details) {
                $this->schemaColumns[$alias] = $details['column'];
            }
        }

        return $this->schemaColumns;
    }

    protected function createColumnFromString($columnName, $label)
    {
        $modelClassName = SolutionSchema::getModelClass($this->model->collection->getModelClassName());

        $autoLabelled = false;

        if ($label === null) {
            $label = StringTools::wordifyStringByUpperCase($columnName);
            $autoLabelled = true;
        }

        $schemaColumns = $this->getSchemaColumns();
        /** @var Model $sampleModel */
        $sampleModel = new $modelClassName();
        $decorator = $sampleModel->getDecorator();

        // Try and convert this to a ModelColumn
        if (isset($schemaColumns[$columnName])) {
            if ($schemaColumns[$columnName] instanceof MySqlForeignKeyColumn) {
                $relationships = SolutionSchema::getAllOneToOneRelationshipsForModelBySourceColumnName($this->model->collection->getModelClassName());

                if (isset($relationships[$columnName])) {
                    if ($relationships[$columnName] instanceof OneToOne) {
                        return new Columns\OneToOneRelationshipColumn($relationships[$columnName], $label);
                    }
                }
            }

            return ModelColumn::createTableColumnForSchemaColumn($schemaColumns[$columnName], $label);
        } else {
            // If the property exists as a computed column let's use that.
            if (method_exists($modelClassName, 'Get' . $columnName)) {
                // Let this computed column be treated as a normal String model column.
                return new ModelColumn($columnName, $label);
            }

            // If the property exists in the data decorator
            if (isset($decorator[$columnName])) {
                // Let this computed column be treated as a normal String model column.
                return new ModelColumn($columnName, $label);
            }

            if (preg_match('/^[.\w]+$/', $columnName)) {
                // If it's all characters and contains a full stop it must be a navigation property.
                if (preg_match('/\./', $columnName)) {
                    if ($autoLabelled) {
                        $parts = explode('.', $columnName);
                        $label = StringTools::wordifyStringByUpperCase($parts[sizeof($parts) - 1]);
                    }

                    return new Template('{' . $columnName . '}', $label);
                } else {
                    $relationships = SolutionSchema::getAllRelationshipsForModel($this->model->collection->getModelClassName());

                    if (isset($relationships[$columnName])) {
                        if ($relationships[$columnName] instanceof OneToOne) {
                            return new Columns\OneToOneRelationshipColumn($relationships[$columnName], $label);
                        }
                    }
                }
            }

            return new Columns\Template($columnName, $label);
        }
    }

    protected function createColumnFromObject($object, $label)
    {
        if ($object instanceof Leaf) {
            return new LeafColumn($object, $label);
        }

        return false;
    }

    /**
     * Expands the columns array, creating TableColumn objects where needed.
     * @param array $columns
     * @return TableColumn[]
     */
    protected function inflateColumns($columns)
    {
        $inflatedColumns = [];

        foreach ($columns as $key => $value) {
            $tableColumn = $value;

            $label = !is_numeric($key) ? $key : null;

            if (is_string($tableColumn)) {
                $value = (string)$value;
                $tableColumn = $this->createColumnFromString($value, $label);
            } elseif (is_callable($tableColumn)) {
                $tableColumn = new ClosureColumn($label, $tableColumn);
            } elseif (!($tableColumn instanceof TableColumn)) {
                $tableColumn = $this->createColumnFromObject($tableColumn, $label);
            }

            if ($tableColumn && $tableColumn instanceof TableColumn) {
                if ($tableColumn instanceof LeafColumn) {
                    $leaf = $tableColumn->getLeaf();
                    if ($leaf instanceof BindableLeafTrait) {
                        $event = $leaf->getBindingValueRequestedEvent();
                        $event->clearHandlers();
                        $event->attachHandler(function ($dataKey, $viewIndex = false) {
                            return $this->getDataForPresenter($dataKey, $viewIndex);
                        });
                    }
                }

                $inflatedColumns[] = $tableColumn;
            }
        }

        return $inflatedColumns;
    }

    /**
     * Provides model data to the requesting presenter.
     *
     * This implementation ensures the LeafColumns are able to receive data from the row's model
     *
     * @param string $dataKey
     * @param bool|int $viewIndex
     * @return mixed
     */
    protected function getDataForPresenter($dataKey, $viewIndex = false)
    {
        if (!isset($this->currentRow[$dataKey])) {
            return $this->model->getBoundValue($dataKey, $viewIndex);
        }

        $value = $this->currentRow[$dataKey];

        if ($value instanceof Model) {
            return $value->UniqueIdentifier;
        }

        return $value;
    }

    public function configureFilters()
    {
        $this->getFilterEvent->raise(function (Filter $filter) {
            $this->model->collection->filter($filter);
            $this->model->collectionUpdatedEvent->raise($this->model->collection);
            $this->collectionModifiedEvent->raise($this->model->collection);
        });

        $this->applySort();
    }

    protected function bindEvents(Leaf $leaf)
    {
        if (property_exists($leaf, "getCollectionEvent")) {
            // The getCollectionEvent is raised by other Leaves when they need the filtered
            // collection the table would use to display it's data. This is often used for counting
            // potential refinements to the list.
            $leaf->getCollectionEvent->attachHandler(function () {
                $collection = clone $this->model->originalCollection;
                $this->getFilterEvent->raise(function (Filter $filter) use ($collection) {
                    $collection->filter($filter);
                    $this->model->collectionUpdatedEvent->raise($this->model->collection);
                    $this->collectionModifiedEvent->raise($this->model->collection);
                });

                return $collection;
            });
        }

        if (property_exists($leaf, "searchedEvent")) {
            $leaf->searchedEvent->attachHandler(function () {
                $this->setSearched();
                $this->reRender();
            });
        }

        if (property_exists($leaf, "refreshesPageCollectionEvent")) {
            $leaf->refreshesPageCollectionEvent->attachHandler(function () {
                $this->reRender();
            });
        }
    }

    public function setSearched()
    {
        $this->model->searched = true;
    }

    public function setUnsearchedHtml($unsearchedHtml)
    {
        $this->model->unsearchedHtml = $unsearchedHtml;
    }

    /**
     * Sets the message to appear when No Data is found
     * @param $noDataHtml
     */
    public function setNoDataHtml($noDataHtml)
    {
        $this->model->noDataHtml = $noDataHtml;
    }

    public function setRepeatPagerAtBottom($repeatPagerAtBottom)
    {
        $this->model->repeatPagerAtBottom = $repeatPagerAtBottom;
    }

    /**
     * @deprecated
     * @see addCssClassNames
     */
    public function addTableCssClass($classNames)
    {
        $this->addCssClassNames($classNames);
    }

    protected function beforeRender()
    {
        parent::beforeRender();

        $this->model->columns = $this->inflateColumns($this->columns);
        $this->configureFilters();
    }

    /**
     * Returns the name of the standard view used for this leaf.
     *
     * @return string
     */
    protected function getViewClass()
    {
        return TableView::class;
    }

    public function setGetRowCssClassesEvent(Event $event)
    {
        $this->model->getRowCssClassesEvent = $event;
    }

    protected function parseUrlState(WebRequest $request)
    {
        if ($this->getUrlStateName()) {
            $sort = $request->get($this->getUrlStateName());

            // Need to treat this as string rather than number, as -0 would be seen as the same as 0
            if (StringTools::startsWith($sort, '-')) {
                $sort = substr($sort, 1);
                $asc = false;
            } else {
                $asc = true;
            }

            if (!is_numeric($sort)) {
                return;
            }

            $column = $this->inflateColumns($this->columns)[(int)$sort];
            if ($column instanceof SortableColumn) {
                // Change the sort order.
                $this->model->sortColumn = $column->getSortableColumnName();
                $this->model->sortDirection = $asc;
            }
        }
    }
}
