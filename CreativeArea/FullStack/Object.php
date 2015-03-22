<?php namespace CreativeArea\FullStack;

/**
 * Class Object.
 */
abstract class Object implements \JsonSerializable
{
    /**
     * @Instance
     * @Synchronize
     *
     * @var \stdClass|null
     */
    public $____fs = null;

    /**
     * @return mixed|void
     */
    public function jsonSerialize()
    {
        $map = array();
        if ($this->____fs) { // was provided remotely
            $reflectionClass = & Engine::$current->classForName($this->____fs[ "type" ]);
            foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as &$property) {
                if (!$property->isStatic() && $property->getAnnotation("Synchronize")) {
                    $name = $property->name;
                    $map[ $name ] = & $this->$name;
                }
            }
        } else { // was constructed during this call
            $this->____fs = array(
                "type" => Engine::$current->nameForClass(get_class($this)),
            );
            $reflectionClass = & Engine::$current->classForName($this->____fs[ "type" ]);
            foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as &$property) {
                if (!$property->isStatic() && $property->getAnnotation("Instance")) {
                    $name = $property->name;
                    $map[ $name ] = & $this->$name;
                }
            }
        }

        return $map;
    }
}
