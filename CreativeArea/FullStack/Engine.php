<?php namespace CreativeArea\FullStack;

/**
 * Class Engine.
 */
class Engine
{
    /**
     * @var Engine|null
     */
    public static $current = null;

    /**
     * @var array
     */
    public static $annotations = [
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
    public static function controlClass(&$reflectionClass)
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
    public $annotate;

    /**
     * @var string[]
     */
    public $namespaces = [];

    /**
     * @var \CreativeArea\Storage\Cache|null
     */
    public $cache = null;

    /**
     * @var int
     */
    public $version = 0;

    /**
     * @var \CreativeArea\FileFinder
     */
    public $scriptFileFinder;

    /**
     * @var \CreativeArea\FileFinder
     */
    public $styleFileFinder;

    public function __construct()
    {
        $this->annotate = new \CreativeArea\Annotate(Engine::$annotations);
        $this->scriptFileFinder = new \CreativeArea\FileFinder();
        $this->styleFileFinder = new \CreativeArea\FileFinder();
    }

    /**
     * @param $name
     * @param $result
     */
    private function _getNameDependencies($name, &$result)
    {
        if (!isset($result[$name])) {
            $reflectionClass = & $this->classForName($name);
            $parentClassName = & $reflectionClass->getParentClass()->name;
            if ($parentClassName !== "CreativeArea\\FullStack\\Object") {
                $this->_getNameDependencies($this->nameForClass($parentClassName), $result);
            }
            $dependencies = $reflectionClass->getAnnotation("DependsOn");
            if ($dependencies) {
                foreach ($dependencies as $dependency) {
                    $this->_getNameDependencies($dependency, $result);
                }
            }
            $result[$name] = true;
        }
    }

    /**
     * @var string[][]
     */
    private $nameDependencies = [];

    /**
     * @param string $name
     *
     * @return string
     */
    private function &getNameDependencies($name)
    {
        if (!isset($this->nameDependencies[$name])) {
            $tmp = [];
            $this->_getNameDependencies($name, $tmp);
            $this->nameDependencies[$name] = array_keys($tmp);
        }

        return $this->nameDependencies[$name];
    }

    /**
     * @var bool[]
     */
    private $usedTypes = [];

    /**
     * @var bool[]
     */
    private $clientTypes = [];

    /**
     * @param string $name
     * @param bool   $fromClient
     */
    public function addUsedType($name, $fromClient = false)
    {
        foreach ($this->getNameDependencies($name) as $type) {
            $this->usedTypes[$type] = true;
            if ($fromClient) {
                $this->clientTypes[$type] = true;
            }
        }
    }

    /**
     * @return string[]
     */
    public function getUsedTypes()
    {
        $types = [];
        foreach ($this->usedTypes as $key => $_) {
            if (!isset($this->clientTypes[$key])) {
                $types[] = $key;
            }
        }

        return $types;
    }

    /**
     * @param mixed $set
     */
    public function findAndConstructObjects(&$set)
    {
        foreach ($set as $key => &$item) {
            if (is_array($item)) {
                if (isset($item[ "____fs" ])) {
                    $marker = & $item[ "____fs" ];
                    $type = $marker[ "type" ];
                    $reflectionClass = & $this->classForName($type);
                    unset($item[ "____fs" ]);
                    $this->findAndConstructObjects($item);
                    $object = $reflectionClass->newInstance();
                    $object->____fs = & $marker;
                    foreach ($item as $name => &$value) {
                        $object->$name = & $value;
                    }
                    $set[ $key ] = & $object;
                    $this->addUsedType($type, true);
                } else {
                    $this->findAndConstructObjects($item);
                }
            }
        }
    }

    /**
     * @param $path
     *
     * @return string
     */
    public function getScript($path)
    {
        if (!preg_match("/\\.js$/", $path)) {
            $path = "$path.js";
        }

        return $this->scriptFileFinder->content($path);
    }

    /**
     * @param $path
     *
     * @return string
     */
    public function getStyle($path)
    {
        if (!preg_match("/\\.scss$/", $path)) {
            $path = "$path.scss";
        }

        return $this->styleFileFinder->exists($path);
    }

    /**
     * @var reflectionClass[]
     */
    private $classForNameCache = [];

    /**
     * @param string $name
     *
     * @return \CreativeArea\Annotate\ReflectionClass
     *
     * @throws Exception
     */
    public function &classForName($name)
    {
        if (!isset($this->classForNameCache[$name])) {
            $path = str_replace("/", "\\", $name);
            foreach ($this->namespaces as $namespace) {
                try {
                    $class = & $this->annotate->getClass($namespace.$path);
                    Engine::controlClass($class);
                    $this->classForNameCache[$name] = & $class;

                    return $class;
                } catch (\ReflectionException $e) {
                }
            }
            throw new Exception("Cannot find class for service $name");
        }

        return $this->classForNameCache[$name];
    }

    /**
     * @var string[]
     */
    private $nameForClassCache = [];

    /**
     * @param string $className
     *
     * @return string
     *
     * @throws Exception
     */
    public function nameForClass($className)
    {
        if (!isset($this->nameForClassCache[$className])) {
            $offset = -strlen($className);
            foreach ($this->namespaces as $namespace) {
                $length = strlen($namespace);
                if (strrpos($className, $namespace, $offset) !== false) {
                    $serviceName = str_replace("\\", "/", substr($className, strlen($namespace)));
                    $testClass = $this->classForName($serviceName);
                    if ($testClass->name !== $className) {
                        throw new Exception("class $className matches service $serviceName which matches class $testClass->name");
                    }

                    return ($this->nameForClassCache[$className] = $serviceName);
                }
            }
            throw new Exception("cannot find service name for class $className");
        }

        return $this->nameForClassCache[$className];
    }

    /**
     * @param string $name
     *
     * @throws Exception
     *
     * @return Descriptor
     */
    private function &generateDescriptor($name)
    {
        $descriptor = new Descriptor();
        $descriptor->build($name, $this);

        return $descriptor;
    }

    /**
     * @var Descriptor[]
     */
    public $descriptorMemoryCache = [];

    /**
     * @param  $name
     *
     * @return Descriptor
     */
    public function &getDescriptor($name)
    {
        static $dummy = null;
        if ($dummy === null) {
            // Needed to load the class before attempting to de-serialize
            $dummy = new Descriptor();
        }

        if (!isset($this->descriptorMemoryCache[ $name ])) {
            if ($this->cache === null) {
                $this->descriptorMemoryCache[ $name ] = & $this->generateDescriptor($name);
            } else {
                $this->descriptorMemoryCache[ $name ] = & $this->cache->getOrCreate($name, $this->version, [&$this, "generateDescriptor"]);
            }
        }

        return $this->descriptorMemoryCache[ $name ];
    }
}
