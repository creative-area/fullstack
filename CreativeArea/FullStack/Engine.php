<?php namespace CreativeArea\FullStack;

/**
 * Class Engine.
 */
class Engine
{
    /**
     * @var int
     */
    private $version = 0;

    /**
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    use Engine_Annotate;
    use Engine_Descriptor;
    use Engine_FileFinder;
    use Engine_JSON;
    use Engine_UsedTypes;

    public function __construct()
    {
        $this->__construct_annotate();
        $this->__construct_descriptor();
        $this->__construct_file_finder();
        $this->__construct_json();
        $this->__construct_used_types();
    }
}
