<?php
require_once "core.cli.php";

echo "Running..." . PHP_EOL;

$request = array("ping", "send-data 1 " . time() . " 1");
$result = processAPIoutput(postAPIrequest($request));

var_dump($result);