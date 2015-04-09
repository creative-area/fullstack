<?php namespace Service;

/**
 * Class Base.
 *
 * @FullStack
 */
abstract class Base
{
    /**
     * @var int
     * @Prototype
     */
    public $number = 66;

    /**
     * @var int
     */
    public $notPrototypeNotInstance = 69;

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
