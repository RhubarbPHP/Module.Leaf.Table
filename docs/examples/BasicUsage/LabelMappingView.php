<?php

namespace Rhubarb\Leaf\Table\Examples\BasicUsage;

use Rhubarb\Leaf\Table\Leaves\Table;
use Rhubarb\Leaf\Views\View;

class LabelMappingView extends View
{
    private $table;

    protected function createSubLeaves()
    {
        $this->registerSubLeaf(
            $this->table = new Table(Job::all())
        );

        $this->table->columns = [
            'Title' => 'JobTitle',                                                  // This one is re-labelled
            'Status' => '<a href="https://google.com/?q={Status}">{Status}</a>',    // This one is a template
            'Sent'
        ];
    }

    protected function printViewContent()
    {
        print $this->table;
    }

}