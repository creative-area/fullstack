<?php

require_once __DIR__."/vendor/autoload.php";

return Symfony\CS\Config\Config::create()
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->exclude("minifiers/external")
            ->in(realpath(__DIR__))
    )
;