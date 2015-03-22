<?php namespace CreativeArea\FullStack;

/**
 * Class Internal.
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
    public static $annotations = array(
        "Class" => array(
            "DependsOn" => "string[]",
            "Script" => "string[]",
            "Style" => "string[]",
        ),
        "Method" => array(
            "Cache" => "flag",
            "Post" => "flag",
            "Script" => "flag",
            "Template" => "flag",
        ),
        "Property" => array(
            "Instance" => "flag",
            "Raw" => "flag",
            "Synchronize" => "flag",
        ),
    );

    /**
     * @param mixed $set
     *
     * @return mixed
     */
    public function findAndConstructObjects(&$set)
    {
        foreach ($set as $key => &$item) {
            if (is_array($item)) {
                if (isset($item[ "____fs" ])) {
                    $marker = & $item[ "____fs" ];
                    $reflectionClass = & $this->classForName($marker[ "type" ]);
                    unset($item[ "____fs" ]);
                    $this->findAndConstructObjects($item);
                    $object = $reflectionClass->newInstance();
                    $object->____fs = & $marker;
                    foreach ($item as $name => &$value) {
                        $object->$name = & $value;
                    }
                    $set[ $key ] = & $object;
                } else {
                    $this->findAndConstructObjects($item);
                }
            }
        }
    }

    /**
     * @var \CreativeArea\Annotate
     */
    public $annotate;

    /**
     * @var string[]
     */
    public $namespaces = array();

    /**
     * @var \CreativeArea\Storage\Cache|null
     */
    public $cache = null;

    /**
     * @var int
     */
    public $version = 0;

    /**
     * @var \CreativeArea\FileFinder[]
     */
    public $fileFinders;

    public function __construct()
    {
        $this->annotate = new \CreativeArea\Annotate(Engine::$annotations);
        $this->fileFinders = array(
            "script" => new \CreativeArea\FileFinder(),
            "style" => new \CreativeArea\FileFinder(),
        );
    }

    /**
     * @param string $namespace
     *
     * @return $this
     */
    public function using($namespace)
    {
        array_unshift($this->namespaces, preg_replace("/\\\\?$/", "\\", $namespace));

        return $this;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function scriptPath($path)
    {
        $this->fileFinders[ "script" ]->addPath($path, 1);

        return $this;
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

        return $this->fileFinders[ "script" ]->content($path);
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function stylePath($path)
    {
        $this->fileFinders[ "style" ]->addPath($path, 1);

        return $this;
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

        return $this->fileFinders[ "style" ]->exists($path);
    }

    /**
     * @param \CreativeArea\Storage $storage
     *
     * @return $this
     */
    public function storage(&$storage)
    {
        $this->cache = $storage === null ? null : new \CreativeArea\Storage\Cache($storage);

        return $this;
    }

    /**
     * @param int $version
     *
     * @return $this
     */
    public function version($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return \CreativeArea\Annotate\ReflectionClass
     *
     * @throws Exception
     */
    public function &classForName($name)
    {
        $path = str_replace("/", "\\", $name);
        foreach ($this->namespaces as $namespace) {
            try {
                return $this->annotate->getClass($namespace.$path);
            } catch (\ReflectionException $e) {
            }
        }
        throw new Exception("Cannot find class for service $name");
    }

    /**
     * @param string $className
     *
     * @return string
     *
     * @throws Exception
     */
    public function nameForClass($className)
    {
        $offset = -strlen($className);
        foreach ($this->namespaces as $namespace) {
            $length = strlen($namespace);
            if (strrpos($className, $namespace, $offset) !== false) {
                $serviceName = str_replace("\\", "/", substr($className, strlen($namespace)));
                $testClass = $this->classForName($serviceName);
                if ($testClass->name !== $className) {
                    throw new Exception("class $className matches service $serviceName which matches class $testClass->name");
                }

                return $serviceName;
            }
        }
        throw new Exception("cannot find service name for class $className");
    }

    /**
     * @param string $name
     *
     * @throws Exception
     *
     * @return Descriptor
     */
    public function &generateDescriptor($name)
    {
        $reflectionClass = & $this->classForName($name);
        $service = new Descriptor();
        $service->build($reflectionClass, $this);

        return $service;
    }

    /**
     * @var Descriptor[]
     */
    public $descriptorMemoryCache = array();

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
            $this->descriptorMemoryCache[ $name ] =
                $this->cache === null
                ? $this->generateDescriptor($name)
                : $this->cache->getOrCreate($name, $this->version, array(&$this, "generateDescriptor"));
        }

        return $this->descriptorMemoryCache[ $name ];
    }
}
