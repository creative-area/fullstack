<?php namespace Minifier;

// Avoid timing class loading
\CreativeArea\FullStack\Script::minify("");

/**
 * Class UglifyJS.
 */
class UglifyJS extends \Minifier
{
    public $environment = "NodeJS";

    public function run($code)
    {
        return \CreativeArea\FullStack\Script::minify($code);
    }
}
