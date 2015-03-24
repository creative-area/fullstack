<?php namespace CreativeArea\FullStack;

/**
 * Trait Engine_Annotate.
 */
trait Engine_Annotate
{
    private function __construct_annotate()
    {
        $this->annotate = new \CreativeArea\Annotate(static::$annotations);
        $this->classForName = new \CreativeArea\Cache(function &($name) {
            return $this->getClassForName($name);
        });
        $this->nameForClass = new \CreativeArea\Cache(function &($className) {
            return $this->getNameForClass($className);
        });
    }

    /**
     * @var array
     */
    private static $annotations = [
        "Class" => [
            "FullStack" => "flag",
            "DependsOn" => "string[]",
            "Script" => "string[]",
            "Style" => "string[]",
        ],
        "Method" => [
            "Cache" => "flag",
            "Path" => "flag",
            "Post" => "flag",
            "Script" => "flag",
            "Style" => "flag",
            "Template" => [
                "normalizeSpace" => true,
            ],
        ],
        "Property" => [
            "Instance" => "flag",
            "Raw" => "flag",
            "Synchronize" => "flag",
        ],
    ];

    /**
     * @var \CreativeArea\Annotate
     */
    private $annotate;

    /**
     * @var string[]
     */
    private $namespaces = [];

    /**
     * @param string $namespace
     */
    public function addNamespace($namespace)
    {
        array_unshift($this->namespaces, preg_replace("/\\\\?$/", "\\", $namespace));
    }

    /**
     * @var \CreativeArea\Cache
     */
    private $classForName;

    /**
     * @param string $name
     *
     * @return \CreativeArea\Annotate\ReflectionClass
     *
     * @throws Exception
     */
    private function &getClassForName($name)
    {
        $path = str_replace("/", "\\", $name);
        foreach ($this->namespaces as $namespace) {
            try {
                $class = & $this->annotate->getClass($namespace.$path);
                if ($class->getAnnotation("FullStack")) {
                    return $class;
                }
            } catch (\ReflectionException $e) {
            }
        }
        throw new Exception("Cannot find class for service $name");
    }

    /**
     * @var \CreativeArea\Cache
     */
    private $nameForClass;

    /**
     * @param string $className
     *
     * @return string
     *
     * @throws Exception
     */
    private function &getNameForClass($className)
    {
        $offset = -strlen($className);
        foreach ($this->namespaces as $namespace) {
            $length = strlen($namespace);
            if (strrpos($className, $namespace, $offset) !== false) {
                $serviceName = str_replace("\\", "/", substr($className, $length));
                $testClass = $this->classForName->get($serviceName);
                if ($testClass->name !== $className) {
                    throw new Exception("class $className matches service $serviceName which matches class $testClass->name");
                }

                return $serviceName;
            }
        }
        throw new Exception("cannot find service name for class $className");
    }
}
