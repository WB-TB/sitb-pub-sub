<?php

namespace CKG\Format;

abstract class TbObject
{
    protected $ckgObject = false;

    public abstract function toArray(): array;

    protected static function fromArray(array $data)
    {
        $class = get_called_class();
        $obj = new $class();
        $config = \Boot::getConfig();
        if (isset($data[$config['ckg']['marker_field']]) &&
            $data[$config['ckg']['marker_field']] === $config['ckg']['marker_value']) {
            $obj->ckgObject = true;
            // unset($data[$config['ckg']['marker_field']]);
        }
        return $obj;
    }

    public static function fromJson(string $json)
    {
        $data = @json_decode($json, true);
        if (!empty(json_last_error())) {
            throw new \Exception("Invalid JSON: " . json_last_error_msg());
        }elseif (!is_array($data)) {
            throw new \Exception("Decoded JSON is not an array.");
        }

        return static::fromArray($data);
    }

    public static function fromJsonToArray(string $json): array
    {
        $data = @json_decode($json, true);
        if (!empty(json_last_error())) {
            throw new \Exception("Invalid JSON: " . json_last_error_msg());
        }elseif (!is_array($data)) {
            throw new \Exception("Decoded JSON is not an array.");
        }

        $assoc = is_array($data) && array_keys($data) !== range(0, count($data) - 1);
        if ($assoc) {
            return [static::fromArray($data)];
        }

        $results = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $results[] = static::fromArray($item);
            }
        }

        return $results;
    }

    public function isCkgObject(): bool
    {
        return $this->ckgObject;
    }

    public function __toString(): string
    {
        $array = $this->toArray();
        $config = \Boot::getConfig();
        $array[$config['ckg']['marker_field']] = $config['ckg']['marker_value'];

        return json_encode($array);
    }
}