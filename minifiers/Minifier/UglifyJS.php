<?php namespace Minifier;

/**
 * Class UglifyJS.
 */
class UglifyJS extends \Minifier
{
    public $environment = "NodeJS";

    public function run($code)
    {
        $result = json_decode(file_get_contents("http://localhost:9615", false, stream_context_create([
            "http" => [
                "header"  => "Content-Type: text/plain\r\n",
                "method"  => "POST",
                "content" => $code,
            ],
        ])));
        if (isset($result->min)) {
            return $result->min;
        }
        throw new \Exception($result->error);
    }
}
