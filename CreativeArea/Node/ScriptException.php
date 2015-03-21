<?php namespace CreativeArea\Node;

/**
 * Class Exception.
 */
class ScriptException extends \CreativeArea\Exception
{
    /**
     * @var stdClass
     */
    public $jsobject;

    /**
     * @param string $jsobject
     */
    public function __construct(&$jsobject)
    {
        parent::__construct($jsobject->message);
        $this->jsobject = & $jsobject;
        if (preg_match("/\\(([^\\)]*):([0-9]+):([0-9]+)\\)/", $jsobject->stack, $tmp)) {
            $this->file = $tmp[1];
            $this->line = 1 * $tmp[2];
            $this->col = 1 * $tmp[3];
        }
    }
}
