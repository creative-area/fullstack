<?php namespace CreativeArea;

/**
 * Class Annotate.
 */
class Annotate
{
    /**
     * @var array
     */
    private $cache = array();

    /**
     * @var (string|array)[][]
     */
    private $definitions;

    /**
     * @param (string|array)[][] $definitions
     */
    public function __construct($definitions)
    {
        $this->definitions = $definitions;
    }

    /**
     * @param Annotate\Reflector $reflector
     *
     * @return Annotate\Annotations
     *
     * @throws Exception
     */
    public function getAnnotations(&$reflector)
    {
        $tmp = array();
        $className = get_class($reflector);
        if (!preg_match("/(Class|Method|Property)$/", $className, $tmp)) {
            throw new Annotate\Exception("Cannot get annotations for a parameter of class '$className'");
        }

        return new Annotate\Annotations($reflector->getDocComment(), $this->definitions[ $tmp[1] ]);
    }

    /**
     * @param string $className
     *
     * @return Annotate\ReflectionClass
     */
    public function &getClass($className)
    {
        $key = $className;
        if (!isset($this->cache[ $key ])) {
            $this->cache[ $key ] = new Annotate\ReflectionClass($className, $this);
        }

        return $this->cache[ $key ];
    }

    /**
     * @param string $className
     * @param string $name
     *
     * @return Annotate\ReflectionMethod
     */
    public function &getMethod($className, $name)
    {
        $key = "$className::$name";
        if (!isset($this->cache[ $key ])) {
            $this->cache[ $key ] = new Annotate\ReflectionMethod($className, $name, $this);
        }

        return $this->cache[ $key ];
    }

    /**
     * @param string $className
     * @param string $name
     *
     * @return Annotate\ReflectionProperty
     */
    public function &getProperty($className, $name)
    {
        $key = "$className::\$$name";
        if (!isset($this->cache[ $key ])) {
            $this->cache[ $key ] = new Annotate\ReflectionProperty($className, $name, $this);
        }

        return $this->cache[ $key ];
    }
}
