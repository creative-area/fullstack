<?php namespace Service;

/**
 * Class First.
 *
 * @Script first.js
 * @Style first.scss
 * @Style styleMethod()
 */
class First extends Base
{
    /**
     * @return string
     * @Style
     */
    public function styleMethod()
    {
        return "
        \$color: #888;
        body {
            color: \$color;
        }
        ";
    }

    /**
     * @var bool
     * @Instance
     */
    public $constructServiceCalled = false;

    public function __construct_instance()
    {
        $this->constructServiceCalled = true;
    }

    /**
     * @return string
     * @Script
     */
    public function helloWorld()
    {
        return '
        console.log( "hello world" );
        ';
    }

    /**
     * @param $nb
     *
     * @return string
     * @Script
     * @Cache
     */
    public function twice($nb)
    {
        return '
        return $nb * 2;
        ';
    }

    /**
     * @param $nb
     *
     * @return mixed
     */
    public function remoteTwice($nb)
    {
        return $nb * 2;
    }
}
