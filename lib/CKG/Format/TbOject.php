<?php

namespace CKG\Format;

abstract class TbObject extends DbObject
{
    public abstract function toArray(): array;
    public abstract function fromArray(array $data);
}