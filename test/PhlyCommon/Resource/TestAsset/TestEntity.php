<?php

namespace PhlyCommonTest\Resource\TestAsset;

use Laminas\InputFilter\InputFilterInterface as InputFilter;
use PhlyCommon\Entity;

class TestEntity implements Entity
{
    protected $inputFilter;

    public function setInputFilter(InputFilter $filter)
    {
        $this->inputFilter = $filter;
    }

    public function getInputFilter(): InputFilter
    {
        return $this->inputFilter;
    }

    public function isValid(): bool
    {
        return true;
    }

    public function fromArray(array $array)
    {
        foreach ($array as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function toArray()
    {
        return (array) $this;
    }
}
