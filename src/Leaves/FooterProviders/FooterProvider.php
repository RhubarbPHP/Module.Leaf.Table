<?php

namespace Rhubarb\Leaf\Table\Leaves\FooterProviders;

use Rhubarb\Leaf\Table\Leaves\Table;

/**
 * A simple abstract to implement if you want a table footer.
 *
 */
abstract class FooterProvider
{
    /**
     * @var Table
     */
    protected $table;

    public abstract function printFooter();

    public function setTable($table)
    {
        $this->table = $table;
    }
}