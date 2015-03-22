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
    public $code = array();

    /**
     * @var string[][]
     */
    public $styleFiles = array();

    /**
     * @var null|string[]
     */
    public $dependencies = null;

    /**
     * @var array
     */
    public $properties = array();

    /**
     * @var bool[]
     */
    public $instanceProperties = array();

    /**
     * @var bool
     */
    public $instantiate = false;

    /**
     * @var string[][]
     */
    public $methods = array(
        "Post" => array(),
        "Script" => array(),
        "Remote" => array(),
    );

    /**
     * @return string
     */
    public function toScript()
    {
        return $this->abstract ?
            Script::object(array(
                "abstract" => true,
                "dependencies" => $this->dependencies,
                "code" => Script::object(array(
                    "script" => Script::createFunction(array(), $this->code["Script"]),
                    "style" => isset($this->code["Style"]) ? json_encode($this->code["Style"]) : null,
                )),
            )) :
            Script::object(array(
                "dependencies" => $this->dependencies,
                "instantiate" => $this->instantiate,
                "code" => Script::object(array(
                    "script" => Script::createFunction(array(), $this->code["Script"]),
                    "style" => isset($this->code["Style"]) ? json_encode($this->code["Style"]) : null,
                )),
                "properties" => Script::object($this->properties),
                "iProperties" => Script::object($this->instanceProperties),
                "methods" => Script::object(array(
                    "post" => Script::createFunction(array("post"),
                            "return ".Script::object($this->methods[ "Post" ]).";"),
                    "script" => Script::object($this->methods[ "Script" ]),
                    "remote" => Script::createFunction(array("remote"),
                            "return ".Script::object($this->methods[ "Remote" ]).";"),
                )),
            ));
    }

    /**
     * @param \CreativeArea\Annotate\ReflectionClass $reflectionClass
     * @param \CreativeArea\FullStack                $fullStack
     *
     * @throws Exception
     */
    public function build(&$reflectionClass, &$fullStack)
    {
        if (!$reflectionClass->isSubclassOf("CreativeArea\\FullStack\\Object")) {
            throw new Exception("class '$reflectionClass->name' is not a service (it does not inherit from CreativeArea\\FullStack\\Object)");
        }

        if ($reflectionClass->name === "CreativeArea\\FullStack\\Object") {
            throw new Exception("class 'CreativeArea\\FullStack\\Object' cannot be described");
        }

        $methodsToIgnore = array(
            "__construct" => true,
            "__construct_service" => true,
            "__construct_instance" => true,
            "__construct_execution" => true,
            "jsonSerialize" => true,
        );

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
            $this->parent = $fullStack->nameForClass($parentClass->name);
        }

        // DEPENDENCIES
        $this->dependencies = $reflectionClass->getAnnotation("DependsOn");

        // CODE
        foreach (array("Script", "Style") as $type) {
            $list = $reflectionClass->getAnnotation($type);
            $parts = array();
            if ($list) {
                $method = $type === "Script" ? "_getScript" : "_getStyle";
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
                    $parts[] = $fullStack->$method($filename);
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
                    $this->styleFiles = array(
                        "parent" => array(),
                        "own" => $parts,
                    );
                    if ($this->parent) {
                        $parentStyleFiles = & $fullStack->getDescriptor($this->parent)->styleFiles;
                        $this->styleFiles[ "parent" ] = array_merge($parentStyleFiles["parent"], $parentStyleFiles["own"]);
                    } else {
                        $this->styleFiles[ "parent" ] = array();
                    }
                    $this->code[ $type ] = Style::compile(
                        $this->styleFiles[ "parent" ],
                        $this->styleFiles[ "own" ],
                        array(&$fullStack, "_getStyle")
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
                $body = $method->invokeArgs($instance, $nbParameters ? array_fill(0, $nbParameters, null) : array());
                if ($templateAnnotation) {
                    $body = Script::compileTemplate($body, $templateAnnotation->normalizeSpace);
                }
                $methodsArray = & $this->methods[ "Script" ];
            } elseif ($method->getAnnotation("Post")) {
                $args = array("form");
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
