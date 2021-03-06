<?php

namespace Tdt\Core\Spectql\implementation\Universalfilters;

use Tdt\Core\Spectql\implementation\universalfilters\CheckInFunction;
use Tdt\Core\Spectql\implementation\universalfilters\Identifier;
use Tdt\Core\Spectql\implementation\universalfilters\NormalFilterNode;
use Tdt\Core\Spectql\implementation\universalfilters\UniversalFilterNode;

/**
 * Represents an identifier...
 *
 * ... of a Table, a Column or an Alias of one of the two.
 *
 * formats: (interpreted by the UniversalTableManager...)
 *   A) package.package.resource.restparam.restparam:subresource.subresource
 *       with:
 *          * packages: one or more,
 *          * restparams: optional,
 *          * subresources: optional
 *       (Please note the separators!)
 *
 *   B) alias.name_of_column
 *
 *
 */
class Identifier extends UniversalFilterNode
{

    private $value;

    public function __construct($value)
    {

        parent::__construct("IDENTIFIER");
        if (!is_object($value) && !is_array($value)) {
            // Trim the value, identifiers itself will always be replaced by underscores in case of whitespaces
            $this->value = trim($value);
        }
    }

    public function getIdentifierString()
    {
        return $this->value;
    }
}
