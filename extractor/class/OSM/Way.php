<?php


class Way
{
    public $id;
    public $sequence;
    /** @var Node[] $nodes */
    public $nodes = [];

    public $previous = null;
    public $next = null;

    public function __construct($id, $sequence)
    {
        $this->id = $id;
        $this->sequence = $sequence;
    }

    public function addNode(&$node)
    {
        $this->nodes[] = $node;
    }

    public function getNodesTail()
    {
        return $this->nodes[0]->id;
    }

    public function getNodesHead()
    {
        return $this->nodes[count($this->nodes) - 1]->id;
    }


    public function reverse()
    {
        $this->nodes = array_reverse($this->nodes);
        $previous = $this->previous;
        $next = $this->next;
        $this->previous = $next;
        $this->next = $previous;
    }
}
