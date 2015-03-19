<?php namespace CreativeArea\StorageCache;

/**
 * Class Entry.
 */
class Entry
{
    /**
     * @var int
     */
    public $version = 0;

    /**
     * @var mixed|null
     */
    public $value = null;

    /**
     * @param int $version
     *
     * @return bool
     *
     * @throws Exception
     */
    public function isCompatible($version)
    {
        if ($this->value !== null) {
            return $this->version === $version;
        }
        if ($this->version > $version) {
            throw new Exception("version $version is obsolete (latest is $this->version)");
        }

        return false;
    }

    /**
     * @param string $string
     *
     * @return Entry
     */
    public static function &fromString(&$string)
    {
        try {
            $tmp = unserialize($string);
        } catch (Exception $e) {
            $tmp = false;
        }
        if (!$tmp instanceof Entry) {
            $tmp = new Entry();
        }

        return $tmp;
    }
}
