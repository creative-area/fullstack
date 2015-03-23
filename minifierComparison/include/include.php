<?php

set_include_path(implode(PATH_SEPARATOR, [get_include_path(), realpath(__DIR__."/../..")]));
spl_autoload_register();

require_once __DIR__."/../../vendor/autoload.php";
require_once __DIR__."/JSMin.php";

// Avoid timing class loading
\CreativeArea\FullStack\Script::object([]);
