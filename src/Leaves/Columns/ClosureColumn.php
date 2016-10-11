<?php

namespace Rhubarb\Leaf\Table\Leaves\Columns;

use Rhubarb\Stem\Models\Model;

class ClosureColumn extends TableColumn
{
    /** @var callable A closure which returns the cell content when passed the Model and Decorator for the row */
    protected $closure;

    /**
     * @param string $label The heading for the column
     * @param callable $closure A closure which returns the cell content when passed the Model and Decorator for the row
     */
    public function __construct($label = '', callable $closure)
    {
        parent::__construct($label);

        $this->closure = $closure;
    }

    /**
     * @param callable $closure A closure which returns the cell content when passed the Model and Decorator for the row
     */
    public function setClosure(callable $closure)
    {
        $this->closure = $closure;
    }

    protected function getCellValue(Model $row, $decorator)
    {
        $closure = $this->closure;

        return $closure($row, $decorator);
    }
}
