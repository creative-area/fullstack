<?php

require_once __DIR__."/autoload.php";

header("Content-Type: text/plain");

$time = microtime(true);

$service = (new CreativeArea\FullStack())->using("Service")->getService("First");

echo "$service\n\n";

$time = microtime(true) - $time;

$time = round($time * 1000);

echo "Done in $time ms\n\n";

echo json_encode($service, JSON_PRETTY_PRINT);
