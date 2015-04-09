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
                "deps" => $this->dependencies,
                "inst" => $this->instantiate,
                "preScript" => Script::createFunction([], $this->code["Script"]),
                "preStyle" => isset($this->code["Style"]) ? json_encode($this->code["Style"]) : null,
                "props" => Script::object($this->properties),
                "iProps" => Script::object($this->instanceProperties),
                "post" => count($this->methods[ "Post" ]) ? Script::createFunction(["post"],
                        "return ".Script::object($this->methods[ "Post" ]).";") : null,
                "script" => Script::object($this->methods[ "Script" ]),
                "remote" => count($this->methods[ "Remote" ]) ? Script::createFunction(["remote"],
                        "return ".Script::object($this->methods[ "Remote" ]).";") : null,
            ]);
    }
};
