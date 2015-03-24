<?php namespace CreativeArea\FullStack;

/**
 * Trait Engine_Descriptor.
 */
trait Engine_Descriptor
{
    private function __construct_descriptor()
    {
    }

    /**
     * @var \CreativeArea\Storage\Cache|null
     */
    private $cache = null;

    /**
     * @param \CreativeArea\Storage $storage
     */
    public function setStorage(&$storage)
    {
        $this->cache = $storage === null ? null : new \CreativeArea\Storage\Cache($storage);
    }

    /**
     * @var Descriptor[]
     */
    private $descriptorMemoryCache = [];

    /**
     * @param  $name
     *
     * @return Descriptor
     */
    public function &getDescriptor($name)
    {
        static $dummy = null;
        if ($dummy === null) {
            // Needed to load the class before attempting to de-serialize
            $dummy = new Descriptor();
        }

        if (!isset($this->descriptorMemoryCache[ $name ])) {
            if ($this->cache === null) {
                $this->descriptorMemoryCache[ $name ] = & $this->generateDescriptor($name);
            } else {
                $this->descriptorMemoryCache[ $name ] = & $this->cache->getOrCreate($name, $this->version, [&$this, "generateDescriptor"]);
            }
        }

        return $this->descriptorMemoryCache[ $name ];
    }

    /**
     * @param string $className
     *
     * @throws Exception
     *
     * @return Descriptor
     */
    private function &generateDescriptor($className)
    {
        $descriptor = new Descriptor();

        $reflectionClass = & $this->classForName->get($className);

        $methodsToIgnore = [
            "__construct" => true,
            "__construct_class" => true,
            "__construct_instance" => true,
            "__construct_execution" => true,
            "jsonSerialize" => true,
        ];

        if ($reflectionClass->isAbstract()) {
            $descriptor->abstract = true;
            $instance = null;
        } else {
            $instance = $reflectionClass->newInstance();
            if ($reflectionClass->hasMethod("__construct_class")) {
                $instance->__construct_class();
            }
        }

        $parentClass = & $reflectionClass->getParentClass();

        if (!$parentClass || !$parentClass->getAnnotation("FullStack")) {
            $parentClass = null;
        } else {
            $descriptor->parent = $this->nameForClass->get($parentClass->name);
        }

        // DEPENDENCIES
        $descriptor->dependencies = $reflectionClass->getAnnotation("DependsOn");

        // CODE
        foreach (["Script", "Style"] as $type) {
            $list = $reflectionClass->getAnnotation($type);
            $parts = [];
            if ($list) {
                $getMethod = $type === "Script" ? "getScript" : "getStyle";
                foreach ($list as $filename) {
                    if (preg_match("/\\(\\)$/", $filename)) {
                        $methodName = substr($filename, 0, -2);
                        if ($descriptor->abstract) {
                            throw new Exception("cannot call method $methodName of abstract class $className");
                        }
                        try {
                            $method = & $reflectionClass->getMethod($methodName);
                        } catch (\ReflectionException $e) {
                            throw new Exception("unknown method $methodName");
                        }
                        if (!$method->isPublic() || $method->isStatic()) {
                            throw new Exception("method $methodName is non-public or static");
                        }
                        if ($method->getAnnotation("Path")) {
                            $parts[] = $this->$getMethod($method->invoke($instance));
                        } elseif ($method->getAnnotation($type)) {
                            $result = $method->invoke($instance);
                            $parts[] = $type === "Script" ? $result : [$result];
                        } else {
                            throw new Exception("method $methodName is not @$type and not @Path");
                        }
                        $methodsToIgnore[ $methodName ] = true;
                    } else {
                        $parts[] = $this->$getMethod($filename);
                    }
                }
            }
            if ($type === "Script") {
                $descriptor->code[ $type ] = implode(";\n", $parts);
            } else {
                $styleFile = preg_replace("/\\.php$/", ".scss", $reflectionClass->getFileName());
                if (file_exists($styleFile)) {
                    $parts[] = $styleFile;
                }
                if (count($parts)) {
                    $descriptor->styleFiles = [
                        "parent" => [],
                        "own" => $parts,
                    ];
                    if ($descriptor->parent) {
                        $parentStyleFiles = & $this->getDescriptor($descriptor->parent)->styleFiles;
                        $descriptor->styleFiles[ "parent" ] = array_merge($parentStyleFiles["parent"], $parentStyleFiles["own"]);
                    } else {
                        $descriptor->styleFiles[ "parent" ] = [];
                    }
                    $descriptor->code[ $type ] = Style::compile(
                        $descriptor->styleFiles[ "parent" ],
                        $descriptor->styleFiles[ "own" ],
                        function ($path) {
                            return $this->getStyle($path);
                        }
                    );
                }
            }
        }

        // Don't go any further for abstract classes
        if ($descriptor->abstract) {
            return $descriptor;
        }

        // PROPERTIES
        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as &$property) {
            if ($property->isStatic()) {
                continue;
            }
            if ($property->getAnnotation("Instance")) {
                $descriptor->instanceProperties[ $property->name ] = !!$property->getAnnotation("Synchronize");
                continue;
            }
            $value = $property->getValue($instance);
            if (!$property->getAnnotation("Raw")) {
                $value = json_encode($value);
            }
            $descriptor->properties[ $property->name ] = $value;
        }

        // METHODS
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as &$method) {
            if ($method->isStatic()) {
                continue;
            }
            if (isset($methodsToIgnore[ $method->name ])) {
                if ($method->name === "__construct_instance") {
                    $descriptor->instantiate = true;
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
                $methodsArray = & $descriptor->methods[ "Script" ];
            } elseif ($method->getAnnotation("Post")) {
                $args = ["form"];
                $body = "return post(this, ".json_encode($method->name).", form);";
                $methodsArray = & $descriptor->methods[ "Post" ];
            } else {
                $args = array_map(function (&$parameter) {
                    return $parameter->name;
                }, $parameters);
                $body = "return remote(this, ".json_encode($method->name).", arguments);";
                $methodsArray = & $descriptor->methods[ "Remote" ];
            }
            $methodsArray[ $method->name ] = Script::createFunction($args, $body, $method->getAnnotation("Cache"));
        }

        return $descriptor;
    }
}
