<?php namespace CreativeArea;

/**
 * Class Cache.
 */
class Cache
{
    /**
     * @var \Closure
     */
    private $fnCreate;

    /**
     * @var \Closure|null
     */
    private $fnKey;

    /**
     * @param \Closure      $fnCreate
     * @param \Closure|null $fnKey
     */
    public function __construct($fnCreate, $fnKey = null)
    {
        $this->fnCreate = $fnCreate;
        $this->fnKey = $fnKey;
    }

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @param mixed $key
     *
     * @return mixed
     */
    public function &get($key)
    {
        $cacheKey = $this->fnKey ? $this->fnKey->__invoke($key) : $key;
        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = & $this->fnCreate->__invoke($key);
        }

        return $this->cache[$cacheKey];
    }
}
