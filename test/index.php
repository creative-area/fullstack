<?php

ini_set("html_errors", false);

set_include_path(implode(PATH_SEPARATOR, [get_include_path(), __DIR__."/.."]));
spl_autoload_register();

require_once __DIR__."/../vendor/autoload.php";

header("Content-Type: text/plain");

$time = microtime(true);

$fullStack = (new CreativeArea\FullStack())
    ->using("Service")
    ->scriptPath(__DIR__."/script")
    ->stylePath("style");

$base = $fullStack->engine->getDescriptor("Base");

echo $base->toScript()."\n\n";
echo json_encode($base, JSON_PRETTY_PRINT)."\n\n";

$first = $fullStack->engine->getDescriptor("First");

echo $first->toScript()."\n\n";
echo json_encode($first, JSON_PRETTY_PRINT)."\n\n";

$firstInstance = new Service\First();
$firstInstance->__construct_instance();

$firstInstanceEncoded = $fullStack->engine->jsonEncode($firstInstance, JSON_PRETTY_PRINT);
echo "$firstInstanceEncoded\n\n";

var_dump($fullStack->engine->getUsedTypes());

$decoded = & $fullStack->engine->jsonDecode($firstInstanceEncoded);

var_dump($fullStack->engine->getUsedTypes());

var_dump($decoded);

$time = microtime(true) - $time;

$time = round($time * 1000);

echo "Done in $time ms\n\n";
