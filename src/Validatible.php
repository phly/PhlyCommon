<?php
namespace PhlyCommon;

use Laminas\InputFilter\InputFilterAwareInterface;

interface Validatible extends InputFilterAwareInterface
{
    public function isValid();
}
