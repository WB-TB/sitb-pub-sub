<?php

namespace CKG\Format;

use CKG\Format\DbObject;

abstract class TbObject extends DbObject
{
    public abstract function toArray(): array;
    public abstract function fromArray(array $data);

    public function __toString(): string
    {
        return json_encode((array)$this, JSON_PRETTY_PRINT);
    }
}