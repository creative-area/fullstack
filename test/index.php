<?php

ini_set("html_errors", false);

set_include_path(implode(PATH_SEPARATOR, array(get_include_path(), __DIR__."/..")));
spl_autoload_register();

require_once __DIR__."/../vendor/autoload.php";

header("Content-Type: text/plain");

$time = microtime(true);

$fullStack = (new CreativeArea\FullStack\Engine())
    ->using("Service")
    ->scriptPath(__DIR__."/script")
    ->stylePath("style");

$base = $fullStack->getDescriptor("Base");

echo $base->toScript()."\n\n";
echo json_encode($base, JSON_PRETTY_PRINT)."\n\n";

$first = $fullStack->getDescriptor("First");

echo $first->toScript()."\n\n";
echo json_encode($first, JSON_PRETTY_PRINT)."\n\n";

$firstInstance = new Service\First();
$firstInstance->__construct_instance();

CreativeArea\FullStack\Engine::$current = & $fullStack;
$firstInstanceEncoded = json_encode($firstInstance, JSON_PRETTY_PRINT);
echo "$firstInstanceEncoded\n\n";

$decoded = json_decode($firstInstanceEncoded, true);

var_dump($decoded);

$set = [$decoded];

$fullStack->findAndConstructObjects($set);

var_dump($set[0]);

$time = microtime(true) - $time;

$time = round($time * 1000);

echo "Done in $time ms\n\n";
