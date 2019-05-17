<?php
include_once  __DIR__ . "/DB.php";

class Extractor
{
    private $db = NULL;

    public function __construct($db = 'osmdata')
    {
        $this->db = new DB($db);
    }

    public function run($id)
    {
        $points = [];
        $relations = $this->get_test($id);
        foreach ($relations as $relation) {
            $p = [
                "relation_id" => $relation["relation_id"],
                "relation_sequence" => $relation["relation_sequence"],
                "way_id" => $relation["way_id"],
                "way_sequence" => $relation["way_sequence"],
                "node_id" => $relation["node_id"],
                "lat" => $relation["latitude"],
                "lon" => $relation["longitude"]
            ];
            $points[] = $p;
        }
        return $points;
    }


    function get_test($id)
    {
        $query = "
            SELECT
                relations.id as relation_id,
                relation_members.sequence as relation_sequence,
                ways.id as way_id,
                way_nodes.sequence as way_sequence,
                nodes.id as node_id,
                nodes.latitude,
                nodes.longitude
            FROM
                relations
            INNER JOIN relation_members ON relations.id = relation_members.relation_id
            INNER JOIN ways ON ways.id = relation_members.member_id
            INNER JOIN way_nodes ON way_nodes.way_id = ways.id
            INNER JOIN nodes ON way_nodes.node_id = nodes.id
            WHERE
                relations.id = $id
            AND member_type = 'way'
            ORDER BY
                relation_members.sequence,
                way_nodes.sequence DESC;
        ";
        return $this->db->query($query);
    }

    function get_relation_members($id)
    {
        $query = "
        SELECT
            relations.id,
            relation_members.member_type,
            relation_members.member_id,
            relation_members.member_role,
            relation_members.sequence
        FROM
            relations
            INNER JOIN relation_members ON relations.id = relation_members.relation_id
        WHERE
            id = $id
            AND member_type = 'way'
        ORDER BY
            relation_members.sequence;
        ";
        return $this->db->query($query);
    }

    function get_member_nodes($id)
    {
        $query = "
        SELECT
            nodes.latitude,
            nodes.longitude
        FROM
            ways
            INNER JOIN way_nodes ON way_nodes.way_id = ways.id
            INNER JOIN nodes ON way_nodes.node_id = nodes.id
        WHERE
            ways.id = $id
        ORDER BY
            way_nodes.sequence;
        ";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function get_box($id)
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