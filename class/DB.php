<?php

class DB
{
    private $db = NULL;

    public function __construct($db = 'osmdata')
    {
//        $this->db = new mysqli(
//            '192.168.10.158',
//            'fsierra',
//            's1err4f2016h',
//            $db,
//            3306
//        );
        $this->db = new mysqli(
            '127.0.0.1',
            'root',
            'secret',
            $db,
            3307
        );
        if ($this->db == NULL) {
            die("No se pudo conectar");
        }
    }

    public function query($query)
    {
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}