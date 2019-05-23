<?php
include_once __DIR__ . "/DB.php";

class Retriever
{
    private $db = NULL;
    private $cache_dir = __DIR__ . "/../cache/";
    private $remote_api = "https://www.openstreetmap.org/api/0.6/";

    public function __construct($db = 'osmdata')
    {
        @mkdir($this->cache_dir, 0655, true);
    }

    public function get($entity, $id)
    {
        $file = $this->cache_dir . $entity . "-" . $id . ".xml";
        if (!file_exists($file)) {
            $url = $this->remote_api . $entity . "/" . $id . "/full";
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