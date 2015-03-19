<?php namespace CreativeArea;

/**
 * Class File.
 */
class FileStorage implements Storage
{
    /**
     * @var string
     *
     * the directory into which records will be stored
     */
    private $directory;

    /**
     * @param string $directory
     */
    public function __construct($directory)
    {
        $this->directory = realpath($directory).PATH_SEPARATOR;
    }

    /**
     * @param $key
     *
     * @return FileStorage\Record
     */
    public function &getRecord($key)
    {
        return new FileStorage\Record($this->directory, $key);
    }
}
