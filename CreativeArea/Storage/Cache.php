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

// Needed to load the classes before attempting to de-serialize
new Cache\Entry();
