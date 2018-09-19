<?php

namespace Rhubarb\Leaf\Table\Examples\BasicUsage;

use Rhubarb\Leaf\Leaves\Leaf;

class LabelMapping extends Leaf
{
    /** @var LabelMappingModel $model **/
    protected $model;

    protected function getViewClass()
    {
        return LabelMappingView::class;
    }

    protected function createModel()
    {
        return new LabelMappingModel();
    }
}
