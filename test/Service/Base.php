<?php namespace Service;

/**
 * Class Base.
 */
abstract class Base extends \CreativeArea\FullStack\Object
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
