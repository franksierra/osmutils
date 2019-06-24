<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
include __DIR__ . "/class/Extractor.php";

$extractor = new Extractor();
//108089
$box = $extractor->get_box(108089)[0];
//$relations = $extractor->get_relations();
$relations[] = array("relation_id" => 108089);

header("Content-type: image/png");

$scale = 500;

$img_width = distance($box["maxLAT"], $box["minLON"], $box["minLAT"], $box["maxLON"]) * ($scale / 100);
$img_height = distance($box["maxLAT"], $box["minLON"], $box["minLAT"], $box["minLON"]) * ($scale / 100);
$img = imagecreatetruecolor($img_width, $img_height);

//$white = imagecolorallocate($img, 255, 255, 255);
//imagefill($img, 0, 0, $white);


foreach ($relations as $relation) {
    $extract = $extractor->run($relation['relation_id']);
    foreach ($extract["polygons"] as $polygon) {
        $point = $polygon[0];
        unset($polygon[0]);
        $P = point($box, $point['lat'], $point['lon'], $scale, $img_height);
        $firstPx = $P['x'];
        $firstPy = $P['y'];
        $color = $way_id = $point["way_id"];
        foreach ($polygon as $point) {
            $Pn = point($box, $point['lat'], $point['lon'], $scale, $img_height);
            if ($way_id != $point["way_id"]) {
                $color = $way_id;
                $way_id = $point["way_id"];
                if (isset($extract['open_ways'][$way_id])) {
                    imagestring($img, 1, $P['x'], $P['y'], $way_id, $color);
                }
            }
            imageline($img, $P['x'], $P['y'], $Pn['x'], $Pn['y'], $color);
            $P = $Pn;
        }
    }
}
imagepng($img);

function distance($lat1, $lon1, $lat2, $lon2)
{
    $r = 6378;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a =
        sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $d = $r * $c;
    return $d;
}

function point($boundaries, $lat, $lon, $scale = 100, $flip = 0)
{
    $lat = abs($boundaries["minLAT"]) + $lat;
    $lon = abs($boundaries["minLON"]) + $lon;
    $x = $lon * $scale;
    $y = (($lat * $scale) * -1) + $flip;
    return [
        "x" => $x,
        "y" => $y
    ];
}