<?php namespace CreativeArea\Annotate;

trait ReflectionMember
{
    use Reflector;

    /**
     * @var null|ReflectionClass
     */
    private $declaringClass = null;

    /**
     * @return ReflectionClass
     */
    public function &getDeclaringClass()
    {
        if ($this->declaringClass === null) {
            $this->declaringClass = & $this->annotate->getClass(parent::getDeclaringClass()->name);
        }

        return $this->declaringClass;
    }
}
