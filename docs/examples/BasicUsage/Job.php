<?php

namespace Rhubarb\Leaf\Table\Examples\BasicUsage;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\BooleanColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\ModelSchema;

class Job extends Model
{

    /**
     * Returns the schema for this data object.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new ModelSchema("Job");
        $schema->addColumn(
            new AutoIncrementColumn("JobID"),
            new StringColumn("JobTitle", 100),
            new StringColumn("Status", 100),
            new BooleanColumn("Sent"));

        return $schema;
    }
}