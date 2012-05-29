<?php
namespace PhlyCommon;

use Zend\InputFilter\InputFilterAwareInterface;

interface Validatible extends InputFilterAwareInterface
{
    public function isValid();
}
