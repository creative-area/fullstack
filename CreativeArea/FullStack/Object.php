<?php namespace CreativeArea\FullStack;

/**
 * Class Object.
 */
abstract class Object implements \JsonSerializable
{
    /**
     * @var \CreativeArea\FullStack|null
     */
    public static $____fsFS = null;

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
            $reflectionClass = & Object::$____fsFS->classForName($this->____fs[ "type" ]);
            foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as &$property) {
                if (!$property->isStatic() && $property->getAnnotation("Synchronize")) {
                    $name = $property->name;
                    $map[ $name ] = & $this->$name;
                }
            }
        } else { // was constructed during this call
            $this->____fs = array(
                "type" => Object::$____fsFS->nameForClass(get_class($this)),
            );
            $reflectionClass = & Object::$____fsFS->classForName($this->____fs[ "type" ]);
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
