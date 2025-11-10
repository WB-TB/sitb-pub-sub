<?php

namespace CKG\Format;

abstract class TbObject
{
    public abstract function toArray(): array;
    public abstract function fromArray(array $data);
}