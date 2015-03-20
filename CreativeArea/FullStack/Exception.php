<?php namespace CreativeArea\FullStack;

class Exception extends \CreativeArea\Exception
{
    public function __construct($message = "", $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
