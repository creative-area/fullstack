<?php namespace Service;

/**
 * Class First.
 *
 * @Service
 *
 * @Script first.js
 * @Style first.scss
 */
class First extends Base
{
    /**
     * @var bool
     */
    public $constructServiceCalled = false;

    public function __construct_service()
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
    public function double($nb)
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
    public function remoteDouble($nb)
    {
        return $nb * 2;
    }
}
