<?php

namespace Rhubarb\Leaf\Table\Examples\BasicUsage;

use Rhubarb\Leaf\Leaves\Leaf;

class MixedColumns extends Leaf
{
    /** @var MixedColumnsModel $model **/
    protected $model;

    protected function getViewClass()
    {
        return MixedColumnsView::class;
    }

    protected function createModel()
    {
        return new MixedColumnsModel();
    }
}
