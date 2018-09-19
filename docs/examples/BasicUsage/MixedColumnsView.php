<?php

namespace Rhubarb\Leaf\Table\Examples\BasicUsage;

use Rhubarb\Leaf\Controls\Common\Checkbox\Checkbox;
use Rhubarb\Leaf\Table\Leaves\Columns\ClosureColumn;
use Rhubarb\Leaf\Table\Leaves\Columns\LeafColumn;
use Rhubarb\Leaf\Table\Leaves\Columns\ModelColumn;
use Rhubarb\Leaf\Table\Leaves\Columns\Template;
use Rhubarb\Leaf\Table\Leaves\Table;
use Rhubarb\Leaf\Views\View;

class MixedColumnsView extends View
{
    private $table;

    protected function createSubLeaves()
    {
        $this->registerSubLeaf(
            $this->table = new Table(Job::all()),
            $checkbox = new Checkbox("selectedJobs")
        );

        $this->table->columns = [
            new Template("<a href='{JobID}'>View Job</a>", '#'),
            new ModelColumn("JobTitle", "Title"),
            "Sent",
            new ClosureColumn("#<sup>2</sup>", function(Job $job){
                return $job->JobID * $job->JobID;
            }),
            new LeafColumn($checkbox)
        ];
    }

    protected function printViewContent()
    {
        print $this->table;
    }

    public function getDeploymentPackage()
    {
        $package = parent::getDeploymentPackage();
        $package->resourcesToDeploy[] = __DIR__.'/Table.css';

        return $package;
    }
}
