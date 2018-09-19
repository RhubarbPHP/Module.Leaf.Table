<?php

namespace Rhubarb\Leaf\Table\Examples\BasicUsage;

use Rhubarb\Leaf\Table\Leaves\Table;
use Rhubarb\Leaf\Views\View;

class StringColumnsView extends View
{
    private $table;

    protected function createSubLeaves()
    {
        $this->registerSubLeaf(
            $this->table = new Table(Job::all())
        );

        $this->table->columns = [
            "JobTitle",
            "Status",
            "Sent"
        ];
    }

    protected function printViewContent()
    {
        print $this->table;
    }

}
