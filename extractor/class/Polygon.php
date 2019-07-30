<?php


class Polygon
{
    public $ways = [];

    public $tail = -1;
    public $head = 0;
    public $next = 0;

    public function addWay($way)
    {
        if (count($this->ways) == 0) {
            $this->tail = $way->id;
        } else {
            $this->head = $way->id;
        }
        $this->next = $way->next->id;
        $this->ways[] = $way;
    }

    public function isClosed()
    {
        return ($this->tail == $this->next);
    }
}