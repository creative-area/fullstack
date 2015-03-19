<?php namespace CreativeArea;

/**
 * Class Cache.
 */
class StorageCache
{
    /**
     * @var Storage
     */
    private $storage;

    /**
     * @param Storage $storage
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
        $entry = & StorageCache\Entry::fromString($record->read());
        if (!$entry->isCompatible($version)) {
            $record->lock();
            $entry = & StorageCache\Entry::fromString($record->read());
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
new StorageCache\Entry();
