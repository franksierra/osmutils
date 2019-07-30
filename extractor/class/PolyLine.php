<?php


class PolyLine
{
    private $head;
    private $nodes = [];
    private $tail;

    public function __construct($nodes = [])
    {
        if (count($nodes) > 0) {
            $this->head = $nodes[0];
            $this->nodes = $nodes;
            $this->tail = $nodes[count($nodes) - 1];
        }
    }

    public function addNode($node)
    {
        if (count($this->nodes) == 0) {
            $this->head = $node->id;
        }
        $this->nodes[] = $node;
        $this->tail = $node->id;

    }

}
