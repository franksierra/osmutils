<?php


class Tag
{
    public $id;
    public $key;
    public $value;

    public function __construct($id, $key, $value)
    {
        $this->id = $id;
        $this->key = $key;
        $this->value = $value;
    }

}
