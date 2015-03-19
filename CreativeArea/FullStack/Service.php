<?php namespace CreativeArea\FullStack;

/**
 * Class Service.
 */
class Service
{
    /**
     * @var string[]
     */
    public $code = array();

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
    public function __toString()
    {
        function getOrNull(&$array, $key)
        {
            return isset($array[$key]) ? $array[$key] : null;
        }

        return JavaScript::object(array(
            "dependencies" => $this->dependencies,
            "instantiate" => $this->instantiate,
            "code" => JavaScript::object(array(
                "script" => getOrNull($this->code, "Script"),
                "style" => getOrNull($this->code, "Style"),
            )),
            "properties" => JavaScript::object($this->properties),
            "iProperties" => JavaScript::object($this->instanceProperties),
            "methods" => JavaScript::object(array(
                "post" => JavaScript::createFunction(array("post"),
                        "return ".JavaScript::object($this->methods[ "Post" ]).";"),
                "script" => JavaScript::object($this->methods[ "Script" ]),
                "remote" => JavaScript::createFunction(array("remote"),
                        "return ".JavaScript::object($this->methods[ "Remote" ]).";"),
            )),
        ));
    }

    /**
     * @param \CreativeArea\Annotate\ReflectionClass $reflectionClass
     * @param \CreativeArea\FileFinder[]             $fileFinders
     *
     * @throws Exception
     */
    public function build(&$reflectionClass, &$fileFinders)
    {
        if (!$reflectionClass->getAnnotation("Service")) {
            throw new Exception("class '$reflectionClass->name' is not a service");
        }

        $methodsToIgnore = array(
            "__construct" => true,
            "__construct_generate" => true,
            "__construct_instantiate" => true,
            "__construct_execute" => true,
        );

        $instance = $reflectionClass->newInstance();

        // DEPENDENCIES
        $this->dependencies = $reflectionClass->getAnnotation("DependsOn");

        // CODE
        foreach (array(
            "Script" => ";\n",
            "Style" => "\n",
        ) as $type => $glue) {
            $list = $reflectionClass->getAnnotation($type);
            if (!$list) {
                continue;
            }
            $contents = array();
            foreach ($list as $filename) {
                if (preg_match("/^->/", $filename)) {
                    $methodName = substr($filename, 2);
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
                $contents[] = $fileFinders[ $type ]->content($filename);
            }
            $this->code[ $type ] = json_encode(implode($glue, $contents));
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
            $value = $property->getValue($this->instance);
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
                if ($method->name === "__construct_instantiate") {
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
                    $body = JavaScript::compileTemplate($body, $templateAnnotation->normalizeSpace);
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
                $body = "return remote( this, ".json_encode($method->name).", arguments );";
                $methodsArray = & $this->methods[ "Remote" ];
            }
            $methodsArray[ $method->name ] = JavaScript::createFunction($args, $body, $method->getAnnotation("Cache"));
        }
    }
};