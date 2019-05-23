<?php

include __DIR__ . "/class/Validator.php";
include __DIR__ . "/class/Extractor.php";
include __DIR__ . "/class/Retriever.php";
include __DIR__ . "/class/Fixer.php";

$extractor = new Extractor();
$missing_ways = $extractor->run(108089)["empty_ways"];

$api = new Retriever();
$count = 0;
foreach ($missing_ways as $id => $nil) {
    $api->get("way", $id);
    echo "get'em " . $count++ . "\n";
}

$fixer = new Fixer();
$count = 0;
foreach ($missing_ways as $id => $nil) {
    $fixer->run("way", $id);
    echo "fix'em " . $count++ . "\n";
}