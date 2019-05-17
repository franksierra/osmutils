<?php
include_once __DIR__ . "/DB.php";

class getMissing
{
    private $db = NULL;
    private $cache_dir = __DIR__ . "/../cache/";
    private $remote_api = "https://www.openstreetmap.org/api/0.6/";

    public function __construct($db = 'osmdata')
    {
        $this->db = new DB($db);
        @mkdir($this->cache_dir, 0655, true);
    }

    public function run($missing)
    {
        $count = 0;
        //Get All de Nodes
        foreach ($missing['node'] as $item) {
            $this->request('node', $item);
            echo ++$count . "\n";
        }
        //Get All de Relations
        foreach ($missing['relation'] as $item) {
            $this->request('relation', $item);
            echo ++$count . "\n";
        }
        //Get All de Ways
        foreach ($missing['way'] as $item) {
            $this->request('way', $item);
            echo ++$count . "\n";
        }

    }

    private function request($type, $id)
    {
        $file = $this->cache_dir . $type . "-" . $id . ".xml";
        if (!file_exists($file)) {
            $url = $this->remote_api . $type . "/" . $id;
            $curl = curl_init();
            curl_setopt_array($curl,
                [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => TRUE,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0
                ]
            );
            $response = curl_exec($curl);
            $error = curl_error($curl);
            $status = curl_errno($curl);
            curl_close($curl);
            if ($response != NULL) {
                file_put_contents($file, $response);
            } else {
                return NULL;
            }
        } else {
            $response = file_get_contents($file);
            if ($response == "") {

                return NULL;
            }
        }

        return $response;
    }

}