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
        $members = $this->get_relation_members($id);
        for ($i = 0; $i < count($members); $i++) {
            $nodes = $this->get_member_nodes($members[$i]['member_id']);
            $members[$i]["nodes"] = [];
            if (count($nodes) > 0) {
                $members[$i]["nodes"] = $nodes;
                $members[$i]["tail"] = $nodes[0]["node_id"];
                $members[$i]["head"] = $nodes[count($nodes) - 1]['node_id'];
            }

        }

        $points = [];
        $open_ways = [];
        $empty_ways = [];
        $polygons = [];

        $first_run = true;
        $global_first_node_id = null;
        $global_last_node_id = null;

        $poly = 0;
        $count = count($members);
        for ($i = 0; $i < $count; $i++) {
            $member = $members[$i];
            $nodes = $member["nodes"];
            if (count($nodes) == 0) {
                $empty_ways[$member["member_id"]] = 1;
                continue;
            }
            $current_way_first_node_id = $nodes[0]["node_id"];
            $current_way_last_node_id = $nodes[count($nodes) - 1]['node_id'];

            if (!$first_run) {
                $global_first_node_id = $points[0]["node_id"];
                $global_last_node_id = $points[count($points) - 1]['node_id'];
                if ($current_way_first_node_id == $global_last_node_id) {
//                    echo "OK";
                } elseif ($current_way_last_node_id == $global_last_node_id) {
                    $nodes = array_reverse($nodes);
                } elseif ($current_way_last_node_id == $global_first_node_id) {
                    $points = array_reverse($points);
                    $nodes = array_reverse($nodes);
                } else {
                    $poly++;
                    $open_ways[$member["member_id"]] = $nodes;
                }
            }
            foreach ($nodes as $node) {
                $points[] = [
                    "relation_id" => $member["relation_id"],
                    "relation_sequence" => $member["relation_sequence"],
                    "way_id" => $member["member_id"],
                    "node_sequence" => $node["node_sequence"],
                    "node_id" => $node["node_id"],
                    "lat" => $node["latitude"],
                    "lon" => $node["longitude"],
                    "poly" => $poly
                ];
            }
            $first_run = false;
            if ($points[0]["node_id"] == $points[count($points) - 1]['node_id']) {
                // Se cerro el poligono
                $polygons[] = $points;
                $points = [];
                $first_run = true;
                $current_way_first_node_id = null;
                $current_way_last_node_id = null;
                $global_first_node_id = null;
                $global_last_node_id = null;
            }

        }
        if (count($points) > 0) {
            $polygons[] = $points;
            $points = [];
        }
        return [
            "points" => $points,
            "polygons" => $polygons,
            "open_ways" => $open_ways,
            "empty_ways" => $empty_ways
        ];
    }

    private function get_relation_members($id)
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
            relation_members.sequence ASC;
        ";
        return $this->db->query($query);
    }

    private function get_member_nodes($id)
    {
        $query = "
            SELECT
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
                way_nodes.sequence DESC";
        return $result = $this->db->query($query);
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

    public function get_relations()
    {
        $query = "
            SELECT
                relation_id,
                CAST(v AS DECIMAL) AS val
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
                AND v IN (4,8)
            GROUP BY
                relation_id,
                v 
            ORDER BY
                val
	";
        return $this->db->query($query);

    }

}