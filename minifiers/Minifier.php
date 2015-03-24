<?php

/**
 * Class Minifier.
 */
abstract class Minifier
{
    /**
     * @var string
     */
    public $environment;

    /**
     * @param string $code
     *
     * @return string
     */
    abstract public function run($code);
}
