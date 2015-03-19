<?php namespace CreativeArea\Annotate;

/**
 * Class ReflectionMethod.
 */
class ReflectionMethod extends \ReflectionMethod
{
    use ReflectionMember;

    /**
     * @param string                 $className
     * @param string                 $name
     * @param \CreativeArea\Annotate $annotate
     */
    public function __construct($className, $name, &$annotate)
    {
        parent::__construct($className, $name);
        $this->annotate = & $annotate;
    }
}
