<?php

include __DIR__ . "/class/Validator.php";
include __DIR__ . "/class/fixMissing.php";

$validator = new Validator();
$missing = $validator->run();

$fix = new fixMissing();
$fix->run($missing);