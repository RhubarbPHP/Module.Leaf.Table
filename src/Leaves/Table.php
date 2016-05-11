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
use Rhubarb\Crown\Response\FileResponse;
use Rhubarb\Crown\String\StringTools;
use Rhubarb\Leaf\Leaves\Leaf;
use Rhubarb\Leaf\Leaves\LeafModel;
use Rhubarb\Leaf\Table\Leaves\Columns\ModelColumn;
use Rhubarb\Leaf\Table\Leaves\Columns\LeafColumn;
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
class Table extends Leaf
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
     */
    public $getRowCssClassesEvent;

    /**
     * @var Model
     */
    private $currentRow;

    public function __construct(Collection $list = null, $pageSize = 50, $presenterName = "Table")
    {
        parent::__construct($presenterName);

        $this->getFilterEvent = new Event();

        $this->model->collection = $list;
        $this->model->pageSize = $pageSize;
    }

    protected function onModelCreated()
    {
        parent::onModelCreated();

        $this->model->columnClickedEvent->attachHandler(function($index){
            // Get the inflated columns so we know which one we're dealing with.
            $columns = $this->inflateColumns($this->columns);
            $column = $columns[$index];

            if ($column instanceof SortableColumn) {
                // Change the sort order.
                $this->changeSort($column->getSortableColumnName());
            }

            return $index;
        });
    }

    public function addFooter(FooterProvider $provider)
    {
        $provider->setTable($this);
        
        $this->model->footerProviders[] = $provider;
    }

    public function clearFooters()
    {
        $this->footerProviders = [];
    }

    public function getCollection()
    {
        return $this->model->collection;
    }

    public function exportList()
    {
        $this->configureFilters();

        $cachePath = Application::current()->applicationRootPath."/cache/";

        if (file_exists($cachePath."export.csv")) {
            unlink($cachePath."export.csv");
        }

        $file = $cachePath."export.csv";

        $stream = new CsvStream($file);

        $columns = $this->inflateColumns($this->model->exportColumns);
        $headings = [];

        foreach ($columns as $column) {
            $headings[] = $column->label;
        }

        $stream->setHeaders(
            $headings
        );

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
        $this->collection = $collection;
    }

    protected function configureView()
    {
        parent::configureView();

        $this->view->attachEventHandler("PageChanged", function () {
            $this->onRefresh();
            $this->raiseEventOnViewBridge($this->getPresenterPath(), "OnPageChanged");
        });
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
        if (!$this->schemaColumns) {
            $schema = $this->model->collection->getModelSchema();
            $this->schemaColumns = $schema->getColumns();
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
            if (method_exists($modelClassName, "Get" . $columnName)) {
                // Let this computed column be treated as a normal String model column.
                return new ModelColumn($columnName, $label);
            }

            // If the property exists in the data decorator
            if (isset($decorator[$columnName])) {
                // Let this computed column be treated as a normal String model column.
                return new ModelColumn($columnName, $label);
            }

            if (preg_match("/^[.\w]+$/", $columnName)) {
                // If it's all characters and contains a full stop it must be a navigation property.
                if (preg_match("/\./", $columnName)) {
                    if ($autoLabelled) {
                        $parts = explode(".", $columnName);
                        $label = StringTools::wordifyStringByUpperCase($parts[sizeof($parts) - 1]);
                    }

                    return new Template("{" . $columnName . "}", $label);
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
     */
    protected function inflateColumns($columns)
    {
        $inflatedColumns = [];

        foreach ($columns as $key => $value) {
            $tableColumn = $value;

            $label = (!is_numeric($key)) ? $key : null;

            if (is_string($tableColumn)) {
                $value = (string)$value;
                $tableColumn = $this->createColumnFromString($value, $label);
            } elseif (!($tableColumn instanceof TableColumn)) {
                $tableColumn = $this->createColumnFromObject($tableColumn, $label);
            }

            if ($tableColumn && ($tableColumn instanceof TableColumn)) {
                if ($tableColumn instanceof LeafColumn) {
                    $tableColumn->getLeaf()->replaceEventHandler("GetBoundData", function ($dataKey, $viewIndex = false) {
                        return $this->getDataForPresenter($dataKey, $viewIndex);
                    });
                }

                $inflatedColumns[] = $tableColumn;
            }
        }

        return $inflatedColumns;
    }

    /**
     * Provides model data to the requesting presenter.
     *
     * This implementation ensures the PresenterColumns are effectively receive data from the table row
     *
     * @param string $dataKey
     * @param bool|int $viewIndex
     * @return mixed
     */
    protected function getDataForPresenter($dataKey, $viewIndex = false)
    {
        if (!isset($this->currentRow[$dataKey])) {
            return $this->raiseEvent("GetBoundData", $dataKey, $viewIndex);
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
        });

        $this->applySort();
    }

    protected function bindEvents(Leaf $leaf)
    {
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

    public function addTableCssClass($classNames)
    {
        $classes = $this->model->tableCssClassNames;

        if (!is_array($classes)) {
            $classes = [];
        }

        $classes = array_merge($classes, $classNames);
        $this->tableCssClassNames = $classes;
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

    /**
     * Should return a class that derives from LeafModel
     *
     * @return LeafModel
     */
    protected function createModel()
    {
        $model = new TableModel();

        // Pass through for getRowCssClassesEvent;
        $this->getRowCssClassesEvent = $model->getRowCssClassesEvent;

        return $model;
    }
}
