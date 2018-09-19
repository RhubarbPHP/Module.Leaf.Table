<?php

namespace Rhubarb\Leaf\Table\Examples\BasicUsage;

use Rhubarb\Leaf\Table\Leaves\Columns\ModelColumn;
use Rhubarb\Leaf\Table\Leaves\Table;
use Rhubarb\Leaf\Views\View;

class CellFormattingView extends View
{
    private $table;

    protected function createSubLeaves()
    {
        $this->registerSubLeaf(
            $this->table = new Table(Job::all())
        );

        $this->table->columns = [
            $title = new ModelColumn("JobTitle"),
            "Status",
            "Sent"
        ];

        $title->addCssClass("red");
    }

    protected function printViewContent()
    {
        print $this->table;
    }

}