<?php

namespace PhlyCommon\Filter;

use DateTime;
use Laminas\Filter\FilterInterface;
use MongoDate;

use function is_int;
use function is_numeric;
use function is_string;
use function strtotime;

class Timestamp implements FilterInterface
{
    public function filter($value)
    {
        if ($value instanceof DateTime) {
            $value = $value->getTimestamp();
        } elseif ($value instanceof MongoDate) {
            $value = $value->sec;
        } elseif (is_string($value) && ! is_numeric($value)) {
            $value = strtotime($value);
        } elseif (is_int($value) || (is_string($value) && is_numeric($value))) {
            if (is_string($value)) {
                $value = (int) $value;
            }
            $dt     = new DateTime();
            $return = $dt->setTimestamp($value);
            if ($return->format('Y-m-d H:i:s') === $value) {
                $value = $_SERVER['REQUEST_TIME'];
            }
        } else {
            $value = $_SERVER['REQUEST_TIME'];
        }
        return (int) $value;
    }
}
