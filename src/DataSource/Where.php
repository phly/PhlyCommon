<?php

namespace PhlyCommon\DataSource;

use InvalidArgumentException;

use function in_array;
use function strtoupper;

class Where
{
    public $type;
    public $key;
    public $comparison;
    public $value;

    public function __construct($type, $key, $comparison, $value)
    {
        $type = strtoupper($type);
        if (! in_array($type, ['AND', 'OR'])) {
            throw new InvalidArgumentException('Expected "AND" or "OR" for where clause type; received "' . $type . '"');
        }

        $this->type       = $type;
        $this->key        = $key;
        $this->comparison = $comparison;
        $this->value      = $value;
    }
}
