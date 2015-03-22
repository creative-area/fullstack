<?php namespace CreativeArea;

/**
 * Class FullStack.
 */
class FullStack
{
    /**
     * @var FullStack\Engine
     */
    public $engine;

    public function __construct()
    {
        $this->engine = new FullStack\Engine();
    }

    /**
     * @param string $namespace
     *
     * @return $this
     */
    public function using($namespace)
    {
        array_unshift($this->engine->namespaces, preg_replace("/\\\\?$/", "\\", $namespace));

        return $this;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function scriptPath($path)
    {
        $this->engine->scriptFileFinder->addPath($path, 1);

        return $this;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function stylePath($path)
    {
        $this->engine->styleFileFinder->addPath($path, 1);

        return $this;
    }

    /**
     * @param Storage $storage
     *
     * @return $this
     */
    public function storage(&$storage)
    {
        $this->engine->cache = $storage === null ? null : new Storage\Cache($storage);

        return $this;
    }

    /**
     * @param int $version
     *
     * @return $this
     */
    public function version($version)
    {
        $this->engine->version = $version;

        return $this;
    }
}
