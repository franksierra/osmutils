<?php
require "class/OSMData.php";

set_time_limit(-1);

$OSMData = new OSMData('osmdata');

$box = $OSMData->get_box("108089,120027,288247,1521463")[0];

$relations[] = array(
    "relation_id" => 108089,
    "admin_level" => 2
);
$relations[] = array(
    "relation_id" => 120027,
    "admin_level" => 2
);
$relations[] = array(
    "relation_id" => 288247,
    "admin_level" => 2
);
$relations[] = array(
    "relation_id" => 1521463,
    "admin_level" => 2
);
$relations = $OSMData->get_relations();

header("Content-type: image/png");

$scale_factor = 2; //ie: meter to pixel

//$box["minLAT"] = -90;
//$box["maxLAT"] = 90;
//$box["minLON"] = -180;
//$box["maxLON"] = 180;

$img_width = distance($box["maxLAT"], $box["minLON"], $box["minLAT"], $box["maxLON"]) * $scale_factor;
$img_height = distance($box["maxLAT"], $box["minLON"], $box["minLAT"], $box["minLON"]) * $scale_factor;


$img = imagecreatetruecolor($img_width + (10 * $scale_factor), $img_height + (10 * $scale_factor));

foreach ($relations as $relation) {
    $relation = $OSMData->run($relation);
    foreach ($relation->polygons as $polygon) {
        foreach ($polygon->ways as $way) {
            $color = $relation->id;
            if (isset($way->nodes[0])) {
                $node = $way->nodes[0];
                unset($way->nodes[0]);
                $P = point($box, $node->latitude, $node->longitude, $img_width, $img_height);
            }
            if (isset($extract['open_ways'][$way->id])) {
                imagestring($img, 1, $P['x'], $P['y'], "O-" . $way->id, $color);
            }
            if (isset($extract['empty_ways'][$way->id])) {
                imagestring($img, 1, $P['x'], $P['y'], "M-" . $way->id, $color);
            }

            foreach ($way->nodes as $node) {
                $Pn = point($box, $node->latitude, $node->longitude, $img_width, $img_height);
                imageline($img, $P['x'], $P['y'], $Pn['x'], $Pn['y'], $color);
                $P = $Pn;
            }
        }
    }
}
imagepng($img);


function distance($latFrom, $lonFrom, $latTo, $lonTo)
{
    $r = 6378;
    $dLon = deg2rad($lonTo - $lonFrom);
    $dLat = deg2rad($latTo - $latFrom);

    $a =
        pow(sin($dLat / 2), 2) +
        cos(deg2rad($latFrom)) * cos(deg2rad($latTo)) *
        pow(sin($dLon / 2), 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    $d = $r * $c;
    return $d;
}


function lat2Y($box, $latitude, $height = 1)
{

    $WGS84min = log(tan((90. + $box["minLAT"]) * M_PI / 360.)) / (M_PI / 180.);
    $WGS84min = (int)($WGS84min * 2037598.34 / 180);

    $WGS84max = log(tan((90. + $box["maxLAT"]) * M_PI / 360.)) / (M_PI / 180.);
    $WGS84max = (int)($WGS84max * 2037598.34 / 180);

    $WGS84diff = $WGS84max - $WGS84min;
    $WGS84factor = $height / $WGS84diff;

    $y1 = log(tan((90. + $latitude) * M_PI / 360.)) / (M_PI / 180.);
    $y1 = $y1 * 2037598.34 / 180.;
    $y1 = (int)(($y1 - $WGS84min) * $WGS84factor);
    $y = $height - $y1;

    return $y;

}

function lon2X($box, $longitude, $width = 1)
{
    $lonRange = abs($box["maxLON"] - $box["minLON"]);
    $x = (int)((abs($longitude - $box["minLON"]) / $lonRange) * $width);

    return $x;
}

function point($box, $latitude, $longitude, $width = 0, $height = 0)
{
    $x = lon2X($box, $longitude, $width);
    $y = lat2Y($box, $latitude, $height);

    return [
        "x" => $x,
        "y" => $y
    ];
}
