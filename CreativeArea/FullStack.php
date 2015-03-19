<?php namespace CreativeArea;

/**
 * Class FullStack.
 */
class FullStack
{
    /**
     * @var (string|array)[][]
     */
    private static $annotations = array(
        "Class" => array(
            "DependsOn" => "string[]",
            "Service" => "bool",
            "Script" => "string[]",
            "Style" => "string[]",
        ),
        "Method" => array(
            "Cache" => "bool",
            "Post" => "bool",
            "Script" => "bool",
            "Template" => "bool",
        ),
        "Property" => array(
            "Instance" => "bool",
            "Raw" => "bool",
            "Synchronize" => "bool",
        ),
    );

    /**
     * @var Annotate
     */
    private $annotate;

    /**
     * @var string[]
     */
    private $namespaces = array();

    /**
     * @var StorageCache|null
     */
    private $cache = null;

    /**
     * @var int
     */
    private $version = 0;

    /**
     * @var FileFinder[]
     */
    private $fileFinders;

    public function __construct()
    {
        $this->annotate = new Annotate(FullStack::$annotations);
        $this->fileFinders = array(
            "Script" => new FileFinder(),
            "Style" => new FileFinder(),
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
        $this->fileFinders[ "Script" ]->addPath($path, 1);

        return $this;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function stylePath($path)
    {
        $this->fileFinders[ "Style" ]->addPath($path, 1);

        return $this;
    }

    /**
     * @param Storage $storage
     *
     * @return $this
     */
    public function storage(&$storage)
    {
        $this->cache = $storage === null ? null : new StorageCache($storage);

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
     * @return Annotate\ReflectionClass
     *
     * @throws Exception
     */
    private function &classForService($name)
    {
        $path = str_replace("/", "\\", $name);
        foreach ($this->namespaces as $namespace) {
            try {
                return $this->annotate->getClass($namespace.$path);
            } catch (ReflectionException $e) {
            }
        }
        throw new Exception("Cannot find class for service $name");
    }

    /**
     * @param string $name
     *
     * @throws Exception
     *
     * @return FullStack\Service
     */
    private function &generateService($name)
    {
        $reflectionClass = & $this->classForService($name);
        $service = new FullStack\Service();
        $service->build($reflectionClass, $this->fileFinders);

        return $service;
    }

    /**
     * @param  $name
     *
     * @return FullStack\Service
     */
    public function getService($name)
    {
        return $this->cache === null
            ? $this->generateService($name)
            : $this->cache->getOrCreate($name, $this->version, array(&$this, "generateService"));
    }
}
