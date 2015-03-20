<?php namespace Service;

/**
 * Class First.
 *
 * @Service
 * @DependsOn MyOtherService
 *
 * @Script first.js
 * @Style first.scss
 */
class First
{
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
