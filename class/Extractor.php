<?php
include_once __DIR__ . "/DB.php";

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

        // Get all the relation members
        $members = $this->get_relation_members($id);
        $last_lat = null;
        $last_lon = null;
        $last_node_id = null;

        /**
         * relation_id,
         * member_type,
         * member_id,
         * member_role,
         * relation_sequence
         */
        $empty_ways = [];
        $open_ways = [];
        foreach ($members as $member) {
            $nodes = $this->get_member_nodes($member['member_id']);
            /**
             * way_id,
             * node_sequence,
             * node_id,
             * latitude,
             * longitude
             */
            if (count($nodes) == 0) {
                $empty_ways[$member["member_id"]] = 1;
                continue;
            }
            if ($last_lat == null && $last_lon == null && $last_node_id == null) {
                $last_lat = $nodes[0]['latitude'];
                $last_lon = $nodes[0]['longitude'];
                $last_node_id = $nodes[0]["node_id"];
            }
            if ($last_lat + $last_lon != $nodes[0]['latitude'] + $nodes[0]['longitude']) {
                //Reversed ?
                if ($last_lat == $nodes[count($nodes) - 1]['latitude'] && $last_lon == $nodes[count($nodes) - 1]['longitude']) {
                    $nodes = array_reverse($nodes);
                } else {
                    if ($last_node_id == $nodes[count($nodes) - 1]['longitude']) {
                        $nodes = array_reverse($nodes);
                    } else {
                        $open_ways[$member["member_id"]] = $nodes;
                    }
                }
            }
            foreach ($nodes as $node) {
                $p = [
                    "relation_id" => $member["relation_id"],
                    "relation_sequence" => $member["relation_sequence"],
                    "way_id" => $member["member_id"],
                    "node_sequence" => $node["node_sequence"],
                    "node_id" => $node["node_id"],
                    "lat" => $node["latitude"],
                    "lon" => $node["longitude"]
                ];
                $points[] = $p;
                $last_lat = $node['latitude'];
                $last_lon = $node['longitude'];
            }

        }
        return [
            "points" => $points,
            "empty_ways" => $empty_ways,
            "open_ways" => $open_ways
        ];
    }
//
//    public function runfull()
//    {
//        $points = [];
//        $relations = $this->get_testfull();
//        foreach ($relations as $relation) {
//            $p = [
//                "relation_id" => $relation["relation_id"],
//                "relation_sequence" => $relation["relation_sequence"],
//                "way_id" => $relation["way_id"],
//                "way_sequence" => $relation["way_sequence"],
//                "node_id" => $relation["node_id"],
//                "lat" => $relation["latitude"],
//                "lon" => $relation["longitude"]
//            ];
//            $points[] = $p;
//        }
//        return $points;
//    }
//
//
//    function get_test($id)
//    {
//        $query = "
//            SELECT
//                relations.id as relation_id,
//                relation_members.sequence as relation_sequence,
//                ways.id as way_id,
//                way_nodes.sequence as way_sequence,
//                nodes.id as node_id,
//                nodes.latitude,
//                nodes.longitude
//            FROM
//                relations
//            INNER JOIN relation_members ON relations.id = relation_members.relation_id
//            INNER JOIN ways ON ways.id = relation_members.member_id
//            INNER JOIN way_nodes ON way_nodes.way_id = ways.id
//            INNER JOIN nodes ON way_nodes.node_id = nodes.id
//            WHERE
//                relations.id = $id
//            AND member_type = 'way'
//            ORDER BY
//                relation_members.sequence,
//                way_nodes.sequence ASC;
//        ";
//        return $this->db->query($query);
//    }
//
//    function get_testfull()
//    {
//        $query = "
//            SELECT
//                relations.id as relation_id,
//                relation_members.sequence as relation_sequence,
//                ways.id as way_id,
//                way_nodes.sequence as way_sequence,
//                nodes.id as node_id,
//                nodes.latitude,
//                nodes.longitude
//            FROM
//                relations
//            INNER JOIN relation_members ON relations.id = relation_members.relation_id AND relations.id IN (SELECT	relation_id FROM relation_tags WHERE k LIKE 'admin_level' GROUP BY relation_id)
//            INNER JOIN ways ON ways.id = relation_members.member_id
//            INNER JOIN way_nodes ON way_nodes.way_id = ways.id
//            INNER JOIN nodes ON way_nodes.node_id = nodes.id
//            WHERE
//                member_type = 'way'
//            ORDER BY
//                relation_members.sequence,
//                way_nodes.sequence DESC;
//        ";
//        return $this->db->query($query);
//    }

    function get_relation_members($id)
    {
        $query = "
        SELECT
            relations.id as relation_id,
            relation_members.member_type as member_type,
            relation_members.member_id as member_id,
            relation_members.member_role as member_role,
            relation_members.sequence as relation_sequence
        FROM
            relations
            LEFT JOIN relation_members ON relations.id = relation_members.relation_id
        WHERE
            id = $id
            AND member_type = 'way'
        ORDER BY
            relation_members.sequence DESC;
        ";
        return $this->db->query($query);
    }

    function get_member_nodes($id)
    {
        $query = "
            SELECT
                way_nodes.way_id as way_id,
                way_nodes.sequence as node_sequence,
                nodes.id as node_id,
                nodes.latitude as latitude,
                nodes.longitude as longitude  
            FROM
                way_nodes
                INNER JOIN nodes ON way_nodes.node_id = nodes.id 
            WHERE
                way_nodes.way_id = $id 
            ORDER BY
                way_nodes.sequence ASC";
        return $result = $this->db->query($query);
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