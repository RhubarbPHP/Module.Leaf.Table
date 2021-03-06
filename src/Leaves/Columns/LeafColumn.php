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

namespace Rhubarb\Leaf\Table\Leaves\Columns;

require_once __DIR__ . '/TableColumn.php';

use Rhubarb\Leaf\Leaves\Leaf;
use Rhubarb\Stem\Models\Model;

/**
 * A column type which asks another presenter to present inside each cell.
 */
class LeafColumn extends TableColumn
{
    /**
     * @var Leaf
     */
    protected $leaf;

    public function __construct(Leaf $leaf, $label = "")
    {
        parent::__construct($label);

        $this->leaf = $leaf;
    }

    public function getLeaf()
    {
        return $this->leaf;
    }

    /**
     * Implement this to return the content for a cell.
     *
     * @param \Rhubarb\Stem\Models\Model $row
     * @param \Rhubarb\Stem\Decorators\DataDecorator $decorator
     * @return mixed
     */
    protected function getCellValue(Model $row, $decorator)
    {
        ob_start();

        $this->leaf->printWithIndex($row->UniqueIdentifier);

        return ob_get_clean();
    }
}