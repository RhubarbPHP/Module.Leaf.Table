<?php

namespace Rhubarb\Leaf\Table\Examples\BasicUsage;

use Rhubarb\Leaf\Leaves\Leaf;

class CellFormatting extends Leaf
{
    /** @var CellFormattingModel $model **/
    protected $model;

    protected function getViewClass()
    {
        return CellFormattingView::class;
    }

    protected function createModel()
    {
        return new CellFormattingModel();
    }
}
