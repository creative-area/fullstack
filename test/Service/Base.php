<?php namespace Service;

/**
 * Class Base.
 *
 * @FullStack
 */
abstract class Base
{
    /**
     * @param string $string
     *
     * @return string
     *
     * @Cache
     */
    public function lowercase($string)
    {
        return strtolower($string);
    }
}
