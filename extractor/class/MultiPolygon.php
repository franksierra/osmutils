<?php


class MultiPolygon
{
    public $id = null;
    public $level = null;
    public $polygons = [];
    public $polygon;
    public $empty;

    public function __construct($id, $level)
    {
        $this->id = $id;
        $this->level = $level;
        $this->polygon = new Polygon();
    }

    public function addWay($way)
    {
        if (!$this->polygon->isClosed()) {
            $this->polygon->addWay($way);
        }
        if ($this->polygon->isClosed()) {
            $this->polygons[] = $this->polygon;
            $this->polygon = new Polygon();
        }
    }

    public function finishPolygon($empty)
    {
        $this->empty = $empty;
        $this->polygons[] = $this->polygon;
        unset($this->polygon);
    }
}
