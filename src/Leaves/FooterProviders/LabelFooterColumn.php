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

namespace Rhubarb\Leaf\Table\Leaves\FooterProviders;

require_once __DIR__ . '/FooterColumn.php';

use Rhubarb\Leaf\Table\Leaves\Table;

class LabelFooterColumn extends FooterColumn
{
    private $text;

    public function __construct($text, $span)
    {
        $this->text = $text;

        parent::__construct($span);
    }

    public function getCellValue(Table $table)
    {
        return $this->text;
    }
}