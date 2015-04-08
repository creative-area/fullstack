<?php namespace CreativeArea\FullStack;

/**
 * Class Descriptor.
 */
class Descriptor
{
    /**
     * @var bool
     */
    public $abstract = false;

    /**
     * @var null|string
     */
    public $parent = null;

    /**
     * @var string[]
     */
    public $code = [];

    /**
     * @var string[][]
     */
    public $styleFiles = [];

    /**
     * @var null|string[]
     */
    public $dependencies = null;

    /**
     * @var array
     */
    public $properties = [];

    /**
     * @var int[]
     */
    public $instanceProperties = [];

    /**
     * @var bool
     */
    public $instantiate = false;

    /**
     * @var string[][]
     */
    public $methods = [
        "Post" => [],
        "Script" => [],
        "Remote" => [],
    ];

    /**
     * @return string
     */
    public function toScript()
    {
        return $this->abstract ?
            Script::object([
                "abstract" => true,
                "dependencies" => $this->dependencies,
                "code" => Script::object([
                    "script" => Script::createFunction([], $this->code["Script"]),
                    "style" => isset($this->code["Style"]) ? json_encode($this->code["Style"]) : null,
                ]),
            ]) :
            Script::object([
                "dependencies" => $this->dependencies,
                "instantiate" => $this->instantiate,
                "code" => Script::object([
                    "script" => Script::createFunction([], $this->code["Script"]),
                    "style" => isset($this->code["Style"]) ? json_encode($this->code["Style"]) : null,
                ]),
                "properties" => Script::object($this->properties),
                "iProperties" => Script::object($this->instanceProperties),
                "methods" => Script::object([
                    "post" => Script::createFunction(["post"],
                            "return ".Script::object($this->methods[ "Post" ]).";"),
                    "script" => Script::object($this->methods[ "Script" ]),
                    "remote" => Script::createFunction(["remote"],
                            "return ".Script::object($this->methods[ "Remote" ]).";"),
                ]),
            ]);
    }
};
