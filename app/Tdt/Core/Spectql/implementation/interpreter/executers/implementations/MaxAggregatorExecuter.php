<?php

namespace Tdt\Core\Spectql\implementation\interpreter\executers\implementations;

use Tdt\Core\Spectql\implementation\data\UniversalFilterTableContent;
use Tdt\Core\Spectql\implementation\interpreter\executers\implementations\AggregatorFunctionExecuter;

/* max */
class MaxAggregatorExecuter extends AggregatorFunctionExecuter
{

    public function calculateValue(UniversalFilterTableContent $column, $columnId)
    {
        $data = $this->convertColumnToArray($column, $columnId);
        // Instead of using the max() function of php
        // We'll filter our own maximum, numbers can be encapsulated as string
        // and if a "null" value is present, in an array of string-integers, null will be
        // the max.
        $max = "";
        foreach ($data as $entry) {
            if($entry > $max && $entry != "null")
                $max = $entry;
        }
        return $max;
    }

    public function keepFullInfo()
    {
        return false;
    }

    public function getName($name)
    {
        return "max_" . $name;
    }

    public function errorIfNoItems()
    {
        return true;
    }
}
