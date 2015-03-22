<?php namespace CreativeArea\Annotate;

/**
 * Class ReflectionClass.
 */
class ReflectionClass extends \ReflectionClass
{
    use Reflector;

    /**
     * @param string                 $name
     * @param \CreativeArea\Annotate $annotate
     */
    public function __construct($name, &$annotate)
    {
        parent::__construct($name);
        $this->annotate = & $annotate;
    }

    /**
     * @return null|ReflectionClass
     */
    public function &getParentClass()
    {
        static $parentClass;
        static $done;
        if (!$done) {
            $pClass = parent::getParentClass();
            if ($pClass) {
                $parentClass = & $this->annotate->getClass($pClass->name);
            }
        }

        return $parentClass;
    }

    /**
     * @return null|ReflectionMethod
     */
    public function &getConstructor()
    {
        static $constructor = null;
        static $done = false;
        if (!$done) {
            $done = true;
            $pConstructor = parent::getConstructor();
            if ($pConstructor) {
                $constructor = & $this->getMethod($pConstructor->name);
            }
        }

        return $constructor;
    }

    /**
     * @param string $name
     *
     * @return ReflectionMethod
     */
    public function &getMethod($name)
    {
        return $this->annotate->getMethod($this->name, $name);
    }

    /**
     * @param string $name
     *
     * @return ReflectionProperty
     */
    public function &getProperty($name)
    {
        return $this->annotate->getProperty($this->name, $name);
    }

    /**
     * @param null|string $filter
     *
     * @return ReflectionMethod[]
     */
    public function getMethods($filter = -1)
    {
        static $cache = [];
        if (!isset($cache[ $filter ])) {
            $methods = [];
            foreach (parent::getMethods($filter) as $method) {
                $methods[] = & $this->getMethod($method->name);
            }
            $cache[ $filter ] = & $methods;
        }

        return $cache[ $filter ];
    }

    /**
     * @param int $filter
     *
     * @return ReflectionProperty[]
     */
    public function getProperties($filter = -1)
    {
        static $cache = [];
        if (!isset($cache[ $filter ])) {
            $properties = [];
            foreach (parent::getProperties($filter) as $property) {
                $properties[] = & $this->getProperty($property->name);
            }
            $cache[ $filter ] = & $properties;
        }

        return $cache[ $filter ];
    }

    /**
     * @return ReflectionClass[]
     */
    public function getInterfaces()
    {
        static $interfaces;
        if (!$interfaces) {
            $interfaces = [];
            foreach (parent::getInterfaceNames() as $interfaceName) {
                $interfaces[] = & $this->annotate->getClass($interfaceName);
            }
        }

        return $interfaces;
    }
}
