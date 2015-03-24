<?php namespace CreativeArea\FullStack;

/**
 * Class Object.
 */
abstract class Object implements \JsonSerializable
{
    /**
     * @Instance
     * @Synchronize
     *
     * @var \stdClass|null
     */
    public $____fs = null;

    /**
     * @return mixed|void
     */
    public function jsonSerialize()
    {
        return Engine::objectEncode($this);
    }
}
