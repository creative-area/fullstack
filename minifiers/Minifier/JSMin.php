<?php namespace Minifier;

require_once __DIR__."/../external/JSMin.php";

/**
 * Class JSMin.
 */
class JSMin extends \Minifier
{
    public $environment = "PHP";

    public function run($code)
    {
        return \JSMin::minify($code);
    }
}
