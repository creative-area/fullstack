<?php namespace CreativeArea\FileStorage;

/**
 * Class Record.
 */
class Record implements \CreativeArea\Storage\Record
{
    /**
     * @var string
     *
     * the filename corresponding to this record
     */
    private $filename;

    /**
     * @var resource
     *
     * file descriptor for this record
     */
    private $fileHandler;

    /**
     * @param string $directory
     * @param string $key
     *
     * @throws Exception
     */
    public function __construct($directory, $key)
    {
        $this->filename = $directory.str_replace("/", "+", $key);
        $this->fileHandler = fopen($this->filename, "a+");
        if (!$this->fileHandler) {
            throw new Exception("Cannot open $this->filename");
        }
        if (!flock($this->_fileHandler, LOCK_SH)) {
            throw new Exception("Cannot lock (SH) $this->_filename");
        }
    }

    /**
     * @return bool|string
     */
    public function read()
    {
        return file_get_contents($this->filename);
    }

    /**
     * @throws Exception
     */
    public function lock()
    {
        if (!flock($this->_fileHandler, LOCK_EX)) {
            throw new Exception("Cannot lock (EX) $this->filename");
        }
    }

    /**
     * @param string $content
     *
     * @throws Exception
     */
    public function write(&$content)
    {
        fseek($this->fileHandler, 0);
        ftruncate($this->fileHandler, 0);
        if (fwrite($this->_fileHandler, $content) === false) {
            throw new Exception("Cannot write to $this->filename");
        }
    }

    /**
     * we make sure file locks are released.
     */
    public function __destruct()
    {
        flock($this->fileHandler, LOCK_UN);
        fclose($this->fileHandler);
    }
}
