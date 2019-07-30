<?php


class Node
{
    public $id;
    public $latitude; // X Lat
    public $longitude; // Y Lon
    public $sequence;
    public $tags = [];

    public function __construct($id, $latitude, $longitude, $sequence, $tags)
    {
        $this->id = $id;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->sequence = $sequence;
        $this->tags = $tags;
    }

}
