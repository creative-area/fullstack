<?php namespace CreativeArea\Storage;

/**
 * Class Cache.
 */
class Cache
{
    /**
     * @var \CreativeArea\Storage
     */
    private $storage;

    /**
     * @param \CreativeArea\Storage $storage
     */
    public function __construct(&$storage)
    {
        static $dummy = null;
        if ($dummy === null) {
            // Needed to load the class before attempting to de-serialize
            $dummy = new Cache\Entry();
        }
        $this->storage = & $storage;
    }

    /**
     * @param string   $key
     * @param int      $version
     * @param callable $createFunction
     *
     * @return mixed
     */
    public function getOrCreate($key, $version, $createFunction)
    {
        $record = & $this->storage->getRecord($key);
        $entry = & Cache\Entry::fromString($record->read());
        if (!$entry->isCompatible($version)) {
            $record->lock();
            $entry = & Cache\Entry::fromString($record->read());
            if (!$entry->isCompatible($version)) {
                $entry->version = $version;
                $entry->value = call_user_func($createFunction, $key);
                $record->write(serialize($entry));
            }
        }
        $record = null;

        return $entry->value;
    }
}
