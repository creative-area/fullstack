<?php

set_include_path(implode(PATH_SEPARATOR, array(get_include_path(), __DIR__."/..")));
spl_autoload_register();

header("Content-Type: text/plain");

$time = microtime(true);

$service = (new CreativeArea\FullStack())
    ->using("Service")
    ->scriptPath(__DIR__."/script")
    ->stylePath("style")
    ->getService("First");

echo "$service\n\n";

$time = microtime(true) - $time;

$time = round($time * 1000);

echo "Done in $time ms\n\n";

echo json_encode($service, JSON_PRETTY_PRINT);
