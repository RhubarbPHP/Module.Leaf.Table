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

use Rhubarb\Leaf\Leaves\LeafDeploymentPackage;
use Rhubarb\Leaf\Leaves\UrlStateView;
use Rhubarb\Leaf\Paging\Leaves\EventPager;
use Rhubarb\Leaf\Table\Leaves\Columns\SortableColumn;
use Rhubarb\Leaf\Table\Leaves\Columns\Template;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Decorators\DataDecorator;

class TableView extends UrlStateView
{
    /**
     * @var TableModel
     */
    protected $model;

    protected function getViewBridgeName()
    {
        return 'TableViewBridge';
    }

    public function getDeploymentPackage()
    {
        $package = parent::getDeploymentPackage();
        $package->resourcesToDeploy[] = __DIR__ . '/TableViewBridge.js';
        return $package;
    }

    public function createSubLeaves()
    {
        $pager = new EventPager($this->model->collection);

        $this->model->pagerUrlStateNameChangedEvent->attachHandler(function ($name) use ($pager) {
            $pager->setUrlStateName($name);
        });

        $this->registerSubLeaf($pager);

        $pager->setNumberPerPage($this->model->pageSize);
        $pager->setCollection($this->model->collection);

        $this->model->collectionUpdatedEvent->attachHandler(function (Collection $collection) use ($pager) {
            $pager->setCollection($collection);
        });

        $pager->pageChangedEvent->attachHandler(function() {
            $this->model->pageChangedEvent->raise();
        });
    }

    protected function printColumnLabel($label, $classes)
    {
        $classString = implode(" ", $classes);

        if ($classString != "") {
            $classString = " class=\"" . $classString . "\"";
        }

        print "<th" . $classString . ">" . $label . "</th>";
    }

    protected function printCell($content, $classes, $customHtmlAttributes = "", $label = "")
    {
        $classString = implode(" ", $classes);

        if ($classString != "") {
            $classString = " class=\"" . $classString . "\"";
        }

        print "<td" . $classString . " ".$customHtmlAttributes.">" . $content . "</td>";
    }

    public function printViewContent()
    {
        $suppressPagerContent = false;

        if ($this->model->unsearchedHtml && !$this->model->searched) {
            print $this->model->unsearchedHtml;
            $this->leaves["EventPager"]->deploy('top');
            return;
        } elseif (count($this->model->collection) == 0 && $this->model->noDataHtml) {
            print $this->model->noDataHtml;
            $this->leaves["EventPager"]->deploy('top');
            return;
        }

        $this->leaves["EventPager"]->printWithIndex('top');

        ?>
        <div class="list">
            <table<?= $this->model->getClassAttribute(); ?>>
                <thead>
                <tr>
                    <?php

                    $collectionSorts = $this->model->collection->getSorts();

                    $sorts = [];

                    foreach($collectionSorts as $collectionSort){
                        $sorts[$collectionSort->columnName] = $collectionSort->ascending;
                    }

                    foreach ($this->model->columns as $column) {
                        $classes = $column->getCssClasses();

                        if ($column instanceof SortableColumn && $column->sortable) {
                            $classes[] = "sortable";

                            if (isset($sorts[$column->getSortableColumnName()])) {
                                $classes[] = "sorted";

                                if ($sorts[$column->getSortableColumnName()] == false) {
                                    $classes[] = "descending";
                                }
                            }
                        }

                        print "\r\n\t\t\t\t\t";
                        print $this->printColumnLabel($column->label, $classes);
                    }

                    ?>
                </tr>
                </thead>
                <tbody>
                <?php

                $rowNumber = 0;
                foreach ($this->model->collection as $model) {

                    $classes = $this->model->getRowCssClassesEvent->raise($model, $rowNumber);

                    $classString = "";
                    if (!empty($classes) && is_array($classes)) {
                        $classString = implode(" ", $classes);

                        if ($classString != "") {
                            $classString = " class=\"" . $classString . "\"";
                        }
                    }

                    $rowData = $this->model->getAdditionalClientSideRowDataEvent->raise($model, $rowNumber);

                    $rowDataString = "";
                    if (is_array($rowData) && count($rowData)) {
                        $rowDataString .= " data-row-data=\"" . htmlentities(json_encode($rowData)) . "\"";
                    }

                    print "\r\n\t\t\t\t<tr data-row-id=\"" . $model->UniqueIdentifier . "\"$classString$rowDataString>";

                    $decorator = DataDecorator::getDecoratorForModel($model);

                    if (!$decorator) {
                        $decorator = $model;
                    }

                    foreach ($this->model->columns as $column) {
                        $cellContent = $column->getCellContent($model, $decorator);

                        $classes = $column->getCssClasses();


                        if (!($column instanceof Template && (preg_match("/<a/", $cellContent)))) {
                            $classes[] = "clickable";
                        }

                        $customAttributes = $column->getCustomCellAttributes($model);
                        $customAttributesString = "";

                        if (sizeof($customAttributes) > 0) {
                            foreach ($customAttributes as $name => $value) {
                                $customAttributesString .= " " . $name . "=\"" . htmlentities($value) . "\"";
                            }
                        }

                        print "\r\n\t\t\t\t\t";

                        $this->printCell($cellContent, $classes, $customAttributesString, $column->label);
                    }

                    print "\r\n\t\t\t\t</tr>";

                    $rowNumber++;
                }

                ?>
                </tbody>
                <?php

                if (sizeof($this->model->footerProviders) > 0) {
                    print "<tfoot>";

                    foreach ($this->model->footerProviders as $provider) {
                        $provider->printFooter();
                    }

                    print "</tfoot>";
                }

                ?>
            </table>
        </div>
        <?php

        if ($this->model->repeatPagerAtBottom) {
            $this->leaves["EventPager"]->printWithIndex("bottom");
        }
    }
}
