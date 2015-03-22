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
     * @var bool[]
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

    /**
     * @param \CreativeArea\Annotate\ReflectionClass $reflectionClass
     * @param \CreativeArea\FullStack\Engine         $engine
     *
     * @throws Exception
     */
    public function build(&$reflectionClass, &$engine)
    {
        if (!$reflectionClass->isSubclassOf("CreativeArea\\FullStack\\Object")) {
            throw new Exception("class '$reflectionClass->name' is not a service (it does not inherit from CreativeArea\\FullStack\\Object)");
        }

        if ($reflectionClass->name === "CreativeArea\\FullStack\\Object") {
            throw new Exception("class 'CreativeArea\\FullStack\\Object' cannot be described");
        }

        $methodsToIgnore = [
            "__construct" => true,
            "__construct_service" => true,
            "__construct_instance" => true,
            "__construct_execution" => true,
            "jsonSerialize" => true,
        ];

        if ($reflectionClass->isAbstract()) {
            $this->abstract = true;
            $instance = null;
        } else {
            $instance = $reflectionClass->newInstance();
            if ($reflectionClass->hasMethod("__construct_service")) {
                $instance->__construct_service();
            }
        }

        $parentClass = & $reflectionClass->getParentClass();

        if ($parentClass->name === "CreativeArea\\FullStack\\Object") {
            $parentClass = null;
        } else {
            $this->parent = $engine->nameForClass($parentClass->name);
        }

        // DEPENDENCIES
        $this->dependencies = $reflectionClass->getAnnotation("DependsOn");

        // CODE
        foreach (["Script", "Style"] as $type) {
            $list = $reflectionClass->getAnnotation($type);
            $parts = [];
            if ($list) {
                $method = $type === "Script" ? "getScript" : "getStyle";
                foreach ($list as $filename) {
                    if (preg_match("/^->/", $filename)) {
                        $methodName = substr($filename, 2);
                        if ($this->abstract) {
                            throw new Exception("Cannot call method $methodName of abstract class $reflectionClass->name");
                        }
                        try {
                            $method = & $reflectionClass->getMethod($methodName);
                        } catch (ReflectionException $e) {
                            throw new Exception("unknown method '$methodName'");
                        }
                        if (!$method->isPublic() || $method->isStatic()) {
                            throw new Exception("method '$methodName' is non-public or static");
                        }
                        $methodsToIgnore[ $methodName ] = true;
                        $filename = $method->invoke($instance);
                    }
                    $parts[] = $engine->$method($filename);
                }
            }
            $code = "";
            if ($type === "Script") {
                $this->code[ $type ] = implode(";\n", $parts);
            } else {
                $styleFile = preg_replace("/\\.php$/", ".scss", $reflectionClass->getFileName());
                if (file_exists($styleFile)) {
                    $parts[] = $styleFile;
                }
                if (count($parts)) {
                    $this->styleFiles = [
                        "parent" => [],
                        "own" => $parts,
                    ];
                    if ($this->parent) {
                        $parentStyleFiles = & $engine->getDescriptor($this->parent)->styleFiles;
                        $this->styleFiles[ "parent" ] = array_merge($parentStyleFiles["parent"], $parentStyleFiles["own"]);
                    } else {
                        $this->styleFiles[ "parent" ] = [];
                    }
                    $this->code[ $type ] = Style::compile(
                        $this->styleFiles[ "parent" ],
                        $this->styleFiles[ "own" ],
                        [&$engine, "getStyle"]
                    );
                }
            }
        }

        // Don't go any further for abstract classes
        if ($this->abstract) {
            return;
        }

        // PROPERTIES
        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as &$property) {
            if ($property->isStatic()) {
                continue;
            }
            if ($property->getAnnotation("Instance")) {
                $this->instanceProperties[ $property->name ] = !!$property->getAnnotation("Synchronize");
                continue;
            }
            $value = $property->getValue($instance);
            if (!$property->getAnnotation("Raw")) {
                $value = json_encode($value);
            }
            $this->properties[ $property->name ] = $value;
        }

        // METHODS
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as &$method) {
            if ($method->isStatic()) {
                continue;
            }
            if (isset($methodsToIgnore[ $method->name ])) {
                if ($method->name === "__construct_instance") {
                    $this->instantiate = true;
                }
                continue;
            }
            $parameters = $method->getParameters();
            $templateAnnotation = $method->getAnnotation("Template");
            if ($templateAnnotation || $method->getAnnotation("Script")) {
                $args = array_map(function (&$parameter) {
                   return $parameter->name;
                }, $parameters);
                $nbParameters = count($parameters);
                $body = $method->invokeArgs($instance, $nbParameters ? array_fill(0, $nbParameters, null) : []);
                if ($templateAnnotation) {
                    $body = Script::compileTemplate($body, $templateAnnotation->normalizeSpace);
                }
                $methodsArray = & $this->methods[ "Script" ];
            } elseif ($method->getAnnotation("Post")) {
                $args = ["form"];
                $body = "return post(this, ".json_encode($method->name).", form);";
                $methodsArray = & $this->methods[ "Post" ];
            } else {
                $args = array_map(function (&$parameter) {
                    return $parameter->name;
                }, $parameters);
                $body = "return remote(this, ".json_encode($method->name).", arguments);";
                $methodsArray = & $this->methods[ "Remote" ];
            }
            $methodsArray[ $method->name ] = Script::createFunction($args, $body, $method->getAnnotation("Cache"));
        }
    }
};
