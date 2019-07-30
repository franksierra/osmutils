<?php
include_once __DIR__ . "/DB.php";
include_once __DIR__ . "/OSM/Node.php";
include_once __DIR__ . "/OSM/Tag.php";
include_once __DIR__ . "/OSM/Way.php";


include_once __DIR__ . "/Polygon.php";
include_once __DIR__ . "/MultiPolygon.php";

class OSMData
{
    private $db = NULL;

    /** @var Way[] $ways */
    private $ways = [];
    /** @var Node[] $nodes */
    private $nodes = [];

    private $empty = [];

    public function __construct($db = 'osmdata')
    {
        $this->db = new DB($db);
    }

    public function run($relation)
    {
        $this->ways = [];
        $this->nodes = [];
        $this->empty = [];
        $first = null;
        $last = null;

        $db_ways = $this->get_relation_ways($relation["relation_id"]);
        foreach ($db_ways as $db_way) {
            $this->ways[$db_way["id"]] = new Way($db_way["id"], $db_way["sequence"]);
            $this->ways[$db_way["id"]]->previous = $last;
            $db_nodes = $this->get_way_nodes($db_way["id"]);
            if (count($db_nodes) > 0) {
                foreach ($db_nodes as $db_node) {
                    $this->nodes[$db_node["id"]] = new Node(
                        $db_node["id"],
                        $db_node["latitude"],
                        $db_node["longitude"],
                        $db_node["sequence"],
                        []
                    );
                    $this->ways[$db_way["id"]]->addNode($this->nodes[$db_node["id"]]);
                }
            } else {
                $this->empty[$db_way["id"]] = true;
            }
            if ($first == null) {
                $first =& $this->ways[$db_way["id"]];
            }
            $last =& $this->ways[$db_way["id"]];
        }

        unset($db_ways, $db_way, $db_nodes, $db_node);
        $next =& $first;
        foreach (array_reverse($this->ways) as &$way) {
            $way->next = $next;
            $next =& $way;
        }
        $first->previous =& $last;
        unset($next, $first, $last, $way);

        if (count($this->empty) > 0) {
            $o = 0;
        }
        $multiPolygons = new MultiPolygon($relation["relation_id"], $relation["admin_level"]);
        $current = array_values($this->ways)[0];
        $first = $current;
        while ($current != null) {
            $multiPolygons->addWay($current);
            $next = $current->next;
            if ($next->id != $first->id) {
                $current = $next;
            } else {
                $current = null;
            }
        }
        $multiPolygons->finishPolygon($this->empty);

        return $multiPolygons;

    }

//    public function getRelations()
//    {
//
//    }
//
//    public function getTags($entity, $id)
//    {
//        $query = "SELECT * FROM {$entity}_tags WHERE {$entity}_id ={$id}";
//        return $this->db->query($query);
//    }


    private function get_relation_ways($id)
    {
        $query = "
        SELECT
            relations.id as relation_id,
            relation_members.member_type as type,
            relation_members.member_id as id,
            relation_members.member_role as role,
            relation_members.sequence as sequence
        FROM
            relations
            LEFT JOIN relation_members ON relations.id = relation_members.relation_id
        WHERE
            id = $id
            AND member_type = 'way'
        ORDER BY
            relation_members.sequence ASC;
        ";
        return $this->db->query($query);
    }

    private function get_way_nodes($id)
    {
        $query = "
            SELECT
                nodes.id as id,
                nodes.latitude as latitude,
                nodes.longitude as longitude,  
                way_nodes.sequence as sequence
            FROM
                way_nodes
                INNER JOIN nodes ON way_nodes.node_id = nodes.id 
            WHERE
                way_nodes.way_id = $id 
            ORDER BY
                way_nodes.sequence ASC";
        return $result = $this->db->query($query);
    }

    public function get_relations()
    {
        $query = "
            SELECT
                relation_id,
                CAST(v AS DECIMAL) AS admin_level
            FROM
                relation_tags 
            WHERE
                relation_id IN (
                SELECT
                    relation_id 
                FROM
                    relation_tags 
                WHERE
                    relation_id IN ( SELECT relation_id FROM relation_tags WHERE k = 'boundary' GROUP BY relation_id ) 
                    AND k IN ( 'admin_level', 'boundary', 'name', 'type' ) 
                GROUP BY
                    relation_id 
                ) 
                AND k = 'admin_level' 
                AND v IN (2,4,8)
            GROUP BY
                relation_id,
                v 
            ORDER BY
                admin_level
	";
        return $this->db->query($query);
    }

    public function get_box($id)
    {
        $query = "
        SELECT
            MIN(longitude) minLON,
            MAX(longitude) maxLON,
            MIN(latitude) minLAT,
            MAX(latitude) maxLAT
        FROM
            relations
        INNER JOIN relation_members ON relations.id = relation_members.relation_id
        INNER JOIN ways ON ways.id = relation_members.member_id
        INNER JOIN way_nodes ON way_nodes.way_id = ways.id
        INNER JOIN nodes ON way_nodes.node_id = nodes.id
        WHERE
            relations.id = $id
        AND member_type = 'way'
        ";
        return $this->db->query($query);
    }


}
