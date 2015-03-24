<?php namespace CreativeArea\FullStack;

/**
 * Trait Engine_FileFinder.
 */
trait Engine_FileFinder
{
    private function __construct_file_finder()
    {
        $this->scriptFileFinder = new \CreativeArea\FileFinder();
        $this->styleFileFinder = new \CreativeArea\FileFinder();
    }

    /**
     * @var \CreativeArea\FileFinder
     */
    public $scriptFileFinder;

    /**
     * @var \CreativeArea\FileFinder
     */
    public $styleFileFinder;

    /**
     * @param string $path
     *
     * @return string
     */
    private function getScript($path)
    {
        if (!preg_match("/\\.js$/", $path)) {
            $path = "$path.js";
        }

        return $this->scriptFileFinder->content($path);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function getStyle($path)
    {
        if (!preg_match("/\\.scss$/", $path)) {
            $path = "$path.scss";
        }

        return $this->styleFileFinder->exists($path);
    }
}
