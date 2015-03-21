<?php

ini_set("html_errors", false);

set_include_path(implode(PATH_SEPARATOR, array(get_include_path(), __DIR__."/..")));
spl_autoload_register();

require_once __DIR__."/../vendor/autoload.php";

header("Content-Type: text/plain");

$time = microtime(true);

$fullStack = (new CreativeArea\FullStack())
    ->using("Service")
    ->scriptPath(__DIR__."/script")
    ->stylePath("style");

$first = $fullStack->getService("First");

echo $first->toScript(true)."\n\n";

$base = $fullStack->getService("Base");

echo json_encode($first, JSON_PRETTY_PRINT)."\n\n";
echo json_encode($base, JSON_PRETTY_PRINT)."\n\n";

$time = microtime(true) - $time;

$time = round($time * 1000);

echo "Done in $time ms\n\n";
