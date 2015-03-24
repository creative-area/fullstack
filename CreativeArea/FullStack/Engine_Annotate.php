<?php namespace CreativeArea\FullStack;

/**
 * Trait Engine_Annotate.
 */
trait Engine_Annotate
{
    /**
     * @var array
     */
    private static $annotations = [
        "Class" => [
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
     * @param \CreativeArea\Annotate\ReflectionClass $reflectionClass
     *
     * @throws Exception
     */
    private static function controlClass(&$reflectionClass)
    {
        static $baseClass = "CreativeArea\\FullStack\\Object";
        if (!$reflectionClass->isSubclassOf($baseClass)) {
            throw new Exception("class $reflectionClass->name is not a proper class (it does not inherit from $baseClass)");
        }

        if ($reflectionClass->name === $baseClass) {
            throw new Exception("class $baseClass is not a proper class");
        }
    }

    /**
     * @var \CreativeArea\Annotate
     */
    private $annotate;

    /**
     * Trait constructor.
     */
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
                static::controlClass($class);

                return $class;
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
