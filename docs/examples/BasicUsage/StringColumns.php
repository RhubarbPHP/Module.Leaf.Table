<?php

namespace Rhubarb\Leaf\Table\Examples\BasicUsage;

use Rhubarb\Leaf\Leaves\Leaf;

class StringColumns extends Leaf
{
    /** @var StringColumnsModel $model **/
    protected $model;

    protected function getViewClass()
    {
        return StringColumnsView::class;
    }

    protected function createModel()
    {
        return new StringColumnsModel();
    }
}
