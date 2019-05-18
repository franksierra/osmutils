<?php
include_once  __DIR__ . "/DB.php";

class Validator
{
    private $db = NULL;

    public function __construct($db = 'osmdata')
    {
        $this->db = new DB($db);
    }

    public function run()
    {
        $missing = [];
        $relations = $this->get_relations();
        foreach ($relations as $relation) {
            $missing[$relation['member_type']][] = $relation['member_id'];
        }
        return $missing;
    }

    function get_relations()
    {
        $query = "
            SELECT
                count(*),
                relations.id AS relation_id,
                relation_members.sequence AS relation_sequence,
                relation_members.member_id,
                relation_members.member_type,
                nodes.id AS node_id,
                ways.id AS way_id,
                R.id AS R_id
            FROM
                relations
            INNER JOIN relation_members ON relations.id = relation_members.relation_id 
            
             LEFT JOIN nodes ON nodes.id = relation_members.member_id AND relation_members.member_type = 'node'
             LEFT JOIN ways ON ways.id = relation_members.member_id AND relation_members.member_type = 'way'
             LEFT JOIN relations R ON R.id = relation_members.member_id AND relation_members.member_type = 'relation'
            WHERE
                (relation_members.member_type = 'node' AND nodes.id IS NULL)
            OR (relation_members.member_type = 'way' AND ways.id IS NULL)
            OR (relation_members.member_type = 'relation' AND R.id IS NULL)
            GROUP BY
	            relation_members.member_id,
	            relation_members.member_type
	        ";

        return $this->db->query($query);
    }

}