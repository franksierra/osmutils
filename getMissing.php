<?php

include __DIR__ . "/class/Validator.php";
include __DIR__ . "/class/getMissing.php";

$validator = new Validator();

$missing = $validator->run();

$get = new getMissing();
$get->run($missing);