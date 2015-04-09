<?php namespace Minifier;

require_once __DIR__."/../external/JShrink.php";

/**
 * Class JShrink.
 */
class JShrink extends \Minifier
{
    public $environment = "PHP";

    public function run($code)
    {
        return \JShrink\Minifier::minify($code);
    }
}
