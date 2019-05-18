<?php
include_once __DIR__ . "/DB.php";

class fixMissing
{
    private $db = NULL;
    private $entity_handlers = [];
    private $cache_dir = __DIR__ . "/../cache/";
    private $sql_dir = __DIR__ . "/../sqls/";

    public function __construct($db = 'osmdata')
    {
        $this->db = new DB($db);
        @mkdir($this->sql_dir, 0655, true);
        $this->entity_handlers = array(
            "nodes" => null,
            "node_tags" => null,
            "ways" => null,
            "way_tags" => null,
            "way_nodes" => null,
            "relations" => null,
            "relation_tags" => null,
            "relation_members" => null,
        );
        foreach ($this->entity_handlers as $entity => &$handler) {
            $file_name = $this->sql_dir . "fix_" . $entity . ".sql";
            if (is_file($file_name)) {
                unlink($file_name);
            }
            $handler = fopen($file_name, "a+");
            if (!$handler) {
                die();
            }
        }

    }

    public function run($missing)
    {
        $count = 0;
        //Get All de Nodes
        foreach ($missing['node'] as $item) {
            $xml = $this->get_file('node', $item);
            if ($xml != NULL) {
                $data = $this->proccess('node', $xml);
                $this->makeEntity('node', $data);
            }
            echo ++$count . "\n";
        }
        //Get All de Relations
        foreach ($missing['relation'] as $item) {
            $xml = $this->get_file('relation', $item);
            if ($xml != NULL) {
                $data = $this->proccess('relation', $xml);
                $this->makeEntity('relation', $data);
            }
            echo ++$count . "\n";
        }
        //Get All de Ways
        foreach ($missing['way'] as $item) {
            $xml = $this->get_file('way', $item);
            if ($xml != NULL) {
                $data = $this->proccess('way', $xml);
                $this->makeEntity('way', $data);
            }
            echo ++$count . "\n";
        }

    }

    private function get_file($entity, $id)
    {
        $file = $this->cache_dir . $entity . "-" . $id . ".xml";
        if (file_exists($file)) {
            $xml = simplexml_load_file($file);
            if ($xml == FALSE) {
                return NULL;
            }
            return $xml;
        }
        return NULL;
    }

    private function proccess($entity, $xml)
    {
        $json = json_decode(json_encode($xml));
        $attributes = $json->{$entity}->{"@attributes"};

        $dirty_tags = $json->{$entity}->tag ?? [];
        $tags = [];
        foreach ($dirty_tags as $item) {
            if (isset($item->{"@attributes"})) {
                $tags[] = [
                    "key" => $item->{"@attributes"}->k,
                    "value" => $item->{"@attributes"}->v
                ];
            } else {
                $tags[] = [
                    "key" => $item->k,
                    "value" => $item->v
                ];
            }
        }

        $dirty_nodes = $json->{$entity}->nd ?? [];
        $nodes = [];
        $sequence = 0;
        foreach ($dirty_nodes as $item) {
            $nodes[] = [
                "id" => $item->{"@attributes"}->ref,
                "sequence" => $sequence
            ];
            $sequence++;
        }

        $dirty_members = $json->{$entity}->member ?? [];
        $members = [];
        $sequence = 0;
        foreach ($dirty_members as $item) {
            $members[] = [
                "member_type" => $item->{"@attributes"}->type,
                "member_id" => $item->{"@attributes"}->ref,
                "member_role" => $item->{"@attributes"}->role,
                "sequence" => $sequence,
            ];
            $sequence++;
        }


        $data = [
            'id' => $attributes->id,
            'latitude' => $attributes->lat ?? "",
            'longitude' => $attributes->lon ?? "",
            'changeset_id' => $attributes->changeset ?? "",
            'visible' => $attributes->visible ?? "",
            'timestamp' => $attributes->timestamp ?? "",
            'version' => $attributes->version ?? "",
            'uid' => $attributes->uid ?? "",
            'user' => $attributes->user ?? "",
            "tags" => $tags,
            "nodes" => $nodes,
            "relations" => $members
        ];

        return $data;
    }

    private function makeEntity($entity, $datum)
    {
        fwrite(
            $this->entity_handlers[$entity . "s"],
            $this->insert_entity($entity, $datum)
        );
        foreach ($datum["tags"] as $tag) {
            fwrite(
                $this->entity_handlers[$entity . "_tags"],
                $this->insert_entity_tag($entity, $datum["id"], $tag)
            );
        }

        foreach ($datum["nodes"] as $node) {
            fwrite(
                $this->entity_handlers[$entity . "_nodes"],
                $this->insert_entity_node($entity, $datum["id"], $node)
            );
        }
        foreach ($datum["relations"] as $relation) {
            fwrite(
                $this->entity_handlers[$entity . "_members"],
                $this->insert_entity_member($entity, $datum["id"], $relation)
            );
        }

    }

    private function insert_entity($entity, $values)
    {
        $insert_data = array();

        $insert_data["id"] = $values["id"];
        if ($entity == "node") {
            $insert_data["latitude"] = $values["latitude"];
            $insert_data["longitude"] = $values["longitude"];
        }
        $insert_data["changeset_id"] = $values["changeset_id"];
        $insert_data["visible"] = $values["visible"];
        $insert_data["timestamp"] = $values["timestamp"];
        $insert_data["version"] = $values["version"];
        $insert_data["uid"] = $values["uid"];
        $insert_data["user"] = $values["user"];

        if (isset($values["timestamp"])) {
            $insert_data["timestamp"] = str_replace("T", " ", $insert_data["timestamp"]);
            $insert_data["timestamp"] = str_replace("Z", "", $insert_data["timestamp"]);
        }

        return $this->format_output($entity, "s", $insert_data);
    }

    private function insert_entity_tag($entity, $id, $values)
    {
        $insert_data = array(
            $entity . "_id" => $id,
            "k" => $values["key"],
            "v" => $values["value"]
        );

        return $this->format_output($entity, "_tags", $insert_data);
    }


    private function insert_entity_node($entity, $entity_id, $node)
    {
        $insert_data = array(
            $entity . "_id" => $entity_id,
            "node_id" => $node["id"],
            "sequence" => $node["sequence"]
        );

        return $this->format_output($entity, "_nodes", $insert_data);
    }

    private function insert_entity_member($entity, $entity_id, $values)
    {
        $insert_data = array(
            $entity . "_id" => $entity_id,
            "member_type" => $values["member_type"],
            "member_id" => $values["member_id"],
            "member_role" => $values["member_role"],
            "sequence" => $values["sequence"]
        );

        return $this->format_output($entity, "_members", $insert_data);
    }

    private function format_output($entity, $entity_sufix, $insert_data, $format = 'full_sql')
    {
        $table = $entity . $entity_sufix;
        $escaped_keys = array_map('escape', array_keys($insert_data));
        $escaped_values = array_map('escape', array_values($insert_data));

        $return_string = "";
        switch ($format) {
            case 'csv':
                $values = "'" . implode("'; '", $escaped_values) . "'";
                $return_string = $values . "\n";
                break;
            case 'sql':
                $values = "'" . implode("', '", $escaped_values) . "'";
                $return_string = "INSERT INTO " . $table . " VALUES ($values);\n";
                break;
            case "full_sql":
                $keys = implode(", ", $escaped_keys);
                $values = "'" . implode("', '", $escaped_values) . "'";
                $return_string = "INSERT INTO " . $table . " ($keys) VALUES ($values);\n";
                break;

        }
        return $return_string;
    }
}

function escape($string)
{
    return preg_replace('~[\x00\x0A\x0D\x1A\x22\x27\x5C]~u', '\\\$0', $string);
}