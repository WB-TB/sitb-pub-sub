<?php

namespace CKG\Format;

class PubSubObjectWrapper {
    public const CONSUME = 10;
    public const PRODUCE = 11;

    protected $config;
    protected $ckgObject = false;
    protected $type;
    protected $class;
    /** @var TbObject[] */
    protected array $data;

    private function __construct($type, $class, $data = []) {
        $this->type = $type;
        $this->class = $class;
        $this->data = $data;
        $this->config = \Boot::getConfig();
    }

    public static function NewConsume($class) {
        return new self(self::CONSUME, $class);
    }

    public static function NewProduce($class, array $data) {
        return new self(self::PRODUCE, $class, $data);
    }

    public function getData(): array {
        return $this->data;
    }

    public function fromArray(array $data)
    {
        if (!isset($data[$this->config['ckg']['marker_field']]))
            return;

        $markerValueObj = $data[$this->config['ckg']['marker_field']];

        if ($this->type == self::PRODUCE)
            $markerValueClass = $this->config['ckg']['marker_produce'];
        else
            $markerValueClass = $this->config['ckg']['marker_consume'];

        if ($markerValueObj == $markerValueClass) {
            $this->ckgObject = true;
            $class = $this->class;
            // unset($data[$this->config['ckg']['marker_field']]);
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $item) {
                    /** @var TbObject */
                    $x = new $class();
                    $x->fromArray($item);
                    array_push($this->data, $x);
                }
            }
            \Boot::getLogger()->debug("DATA: " . print_r($data['data'], true));
        }
    }

    public function fromJson(string $json)
    {
        $data = @json_decode($json, true);
        if (!empty(json_last_error())) {
            throw new \Exception("Invalid JSON: " . json_last_error_msg());
        }elseif (!is_array($data)) {
            throw new \Exception("Decoded JSON is not an array.");
        }

        return $this->fromArray($data);
    }

    public function toArray(): array {
        $items = [];
        foreach ($this->data as $item) {
            array_push($items, $item->toArray());
        }

        return ['data' => $items];
    }

    public function toJson(): string {
        $data = $this->toArray();

        if ($this->type == self::PRODUCE)
            $data[$this->config['ckg']['marker_field']] = $this->config['ckg']['marker_produce'];
        else
            $data[$this->config['ckg']['marker_field']] = $this->config['ckg']['marker_consume'];

        $jsonStr = json_encode($data);
        return $jsonStr;
    }

    public function isCkgObject(): bool
    {
        return $this->ckgObject;
    }

    public function __toString(): string
    {
        $array = $this->toArray();
        $this->config = \Boot::getConfig();
        // $array[$this->config['ckg']['marker_field']] = $this->config['ckg']['marker_value'];

        return json_encode($array);
    }

    private function getMarker(): array {
        $markerField = $this->config['ckg']['marker_field'];

        if ($this->type == self::PRODUCE)
            $markerValueClass = $this->config['ckg']['marker_produce'];
        else
            $markerValueClass = $this->config['ckg']['marker_consume'];

        return [$markerField, $markerValueClass];
    }
}