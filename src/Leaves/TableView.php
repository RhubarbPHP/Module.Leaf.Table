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
use Rhubarb\Leaf\Presenters\Application\Pager\EventPager;
use Rhubarb\Leaf\Table\Leaves\Columns\SortableColumn;
use Rhubarb\Leaf\Table\Leaves\Columns\Template;
use Rhubarb\Leaf\Views\JQueryView;
use Rhubarb\Leaf\Views\View;
use Rhubarb\Stem\Decorators\DataDecorator;

class TableView extends View
{
    /**
     * @var TableModel
     */
    protected $model;

    protected function getClientSideViewBridgeName()
    {
        return "TableViewBridge";
    }

    public function getDeploymentPackage()
    {
        return new LeafDeploymentPackage(__DIR__ . "/TableViewBridge.js");
    }

    public function createSubLeaves()
    {
        //$pager = new EventPager();

        $this->registerSubLeaf(
            //$pager
        );

        //$this->leaves["pager"]->attachEventHandler("PageChanged", function () {
        //    $this->raiseEvent("PageChanged");
        //});
    }

    protected function configurePresenters()
    {
        //$this->leaves["pager"]->setCollection($this->raiseEvent("GetCollection"));
        //$this->leaves["pager"]->setNumberPerPage($this->pageSize);
    }

    public function printViewContent()
    {
        $suppressPagerContent = false;

        if ($this->model->unsearchedHtml && !$this->model->searched) {
            print $this->model->unsearchedHtml;
            $suppressPagerContent = true;
        } elseif (count($this->model->collection) == 0 && $this->model->noDataHtml) {
            print $this->model->noDataHtml;
            $suppressPagerContent = true;
        }

        //Always print the pager so we get javaScript loading
        //$this->leaves["pager"]->setSuppressContent($suppressPagerContent);
        //print $this->leaves["pager"];

        if ($suppressPagerContent) {
            return;
        }

        ?>
        <div class='list'>
            <table class="<?= $this->getTableCssClass(); ?>">
                <thead>
                <tr>
                    <?php

                    $sorts = $this->model->collection->getSorts();

                    foreach ($this->model->columns as $column) {
                        $classes = $column->getCssClasses();

                        if ($column instanceof SortableColumn) {
                            $classes[] = "sortable";

                            if (isset($sorts[$column->getSortableColumnName()])) {
                                $classes[] = "sorted";

                                if ($sorts[$column->getSortableColumnName()] == false) {
                                    $classes[] = "descending";
                                }
                            }
                        }

                        $classString = implode(" ", $classes);

                        if ($classString != "") {
                            $classString = " class=\"" . $classString . "\"";
                        }

                        print "\r\n\t\t\t\t\t<th" . $classString . ">" . $column->label . "</th>";
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

                    $rowData = $this->model->getAdditionalClientSideRowData->raise($model, $rowNumber);

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

                        $classString = implode(" ", $classes);

                        if ($classString != "") {
                            $classString = " class=\"" . $classString . "\"";
                        }

                        $customAttributes = $column->getCustomCellAttributes($model);
                        $customAttributesString = "";

                        if (sizeof($customAttributes) > 0) {
                            foreach ($customAttributes as $name => $value) {
                                $customAttributesString .= " " . $name . "=\"" . htmlentities($value) . "\"";
                            }
                        }

                        print "\r\n\t\t\t\t\t<td" . $classString . $customAttributesString . ">" . $cellContent . "</td>";
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
            //$this->leaves["pager"]->displayWithIndex("bottom");
        }
    }


    public function getTableCssClass()
    {
        return implode(" ", $this->model->tableCssClassNames);
    }
}
