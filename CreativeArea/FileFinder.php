<?php namespace CreativeArea;

/**
 * Class FileFinder.
 */
class FileFinder
{
    /**
     * @var string[]
     */
    public $path = array();

    /**
     * @param string $path
     *
     * @return int
     */
    private static function isAbsolute(&$path)
    {
        static $absoluteRegExp = null;
        if ($absoluteRegExp === null) {
            $absoluteRegExp = DIRECTORY_SEPARATOR === "\\" ? "/^[A-Z]:\\\\/" : "!^/!";
        }

        return preg_match($absoluteRegExp, $path);
    }

    /**
     * @param string $path
     * @param int    $callStack
     *
     * @throws Exception
     */
    public function addPath($path, $callStack)
    {
        if (!FileFinder::isAbsolute($path)) {
            $path = dirname(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $callStack + 1)[ $callStack ]["file"])."/$path";
        }
        $realPath = realpath($path);
        if ($realPath === false) {
            throw new FileFinder\Exception("directory $path doesn't exist or cannot be reached");
        }
        array_unshift($this->path, "$realPath/");
    }

    /**
     * @param  $fileName
     *
     * @return mixed
     */
    private static function read($fileName)
    {
        try {
            $content = file_get_contents($fileName);
        } catch (Exception $e) {
            $content = false;
        }

        return $content;
    }

    /**
     * @param string   $fileName
     * @param callable $action
     *
     * @return string
     *
     * @throws Exception
     */
    private function find($fileName, $action)
    {
        $result = false;
        if (FileFinder::isAbsolute($fileName)) {
            $result = call_user_func($action, $fileName);
        } else {
            foreach ($this->path as $directory) {
                $result = call_user_func($action, $directory.$fileName);
                if ($result !== false) {
                    break;
                }
            }
        }
        if ($result === false) {
            throw new FileFinder\Exception("file $fileName doesn't exist or cannot be reached");
        }

        return $result;
    }

    /**
     * @param string $fileName
     *
     * @return string
     *
     * @throws Exception
     */
    public function content($fileName)
    {
        return $this->find($fileName, array(get_class($this), "read"));
    }

    /**
     * @param string $fileName
     *
     * @return string
     *
     * @throws Exception
     */
    public function exists($fileName)
    {
        return $this->find($fileName, "realpath");
    }
}
