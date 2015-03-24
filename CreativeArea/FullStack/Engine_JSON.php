<?php namespace CreativeArea\FullStack;

/**
 * Trait Engine_JSON.
 */
trait Engine_JSON
{
    /**
     * Trait constructor.
     */
    private function __construct_json()
    {
    }

    /**
     * @var Engine|null
     */
    private static $encodeInitiator = null;

    /**
     * @param Object $object
     *
     * @return array
     *
     * @throws Exception
     */
    public static function objectEncode(&$object)
    {
        $initiator = & static::$encodeInitiator;
        if ($initiator === null) {
            throw new Exception("json_encode used without an initiator");
        }

        $map = [];
        if ($object->____fs) { // was provided remotely
            $reflectionClass = & $initiator->classForName->get($object->____fs[ "type" ]);
            foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as &$property) {
                if (!$property->isStatic() && $property->getAnnotation("Synchronize")) {
                    $name = $property->name;
                    $map[ $name ] = & $object->$name;
                }
            }
        } else { // was constructed during this call
            $object->____fs = [
                "type" => $initiator->nameForClass->get(get_class($object)),
            ];
            $reflectionClass = & $initiator->classForName->get($object->____fs[ "type" ]);
            foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as &$property) {
                if (!$property->isStatic() && $property->getAnnotation("Instance")) {
                    $name = $property->name;
                    $map[ $name ] = & $object->$name;
                }
            }
            $initiator->addUsedType($object->____fs[ "type" ]);
        }

        return $map;
    }

    /**
     * @param array $set
     */
    private function findAndConstructObjects(&$set)
    {
        foreach ($set as $key => &$item) {
            if ($key !== "____fs" && is_array($item)) {
                $this->findAndConstructObjects($item);
                if (isset($item[ "____fs" ])) {
                    $marker = & $item[ "____fs" ];
                    $type = $marker[ "type" ];
                    $reflectionClass = & $this->classForName->get($type);
                    unset($item[ "____fs" ]);
                    $this->findAndConstructObjects($item);
                    $object = $reflectionClass->newInstance();
                    $object->____fs = & $marker;
                    foreach ($item as $name => &$value) {
                        $object->$name = & $value;
                    }
                    $set[ $key ] = & $object;
                    $this->addUsedType($type, true);
                }
            }
        }
    }

    /**
     * @param string $code
     *
     * @return mixed
     */
    public function &jsonDecode($code)
    {
        $decoded = [json_decode($code, true)];
        $this->findAndConstructObjects($decoded);

        return $decoded[0];
    }

    /**
     * @param mixed $value
     * @param int   $options
     * @param int   $depth
     *
     * @throws Exception
     *
     * @return string
     */
    public function jsonEncode(&$value, $options = 0, $depth = 512)
    {
        if (static::$encodeInitiator !== null) {
            throw new Exception("json_encode already initiated");
        }
        static::$encodeInitiator = & $this;
        $tmp = json_encode($value, $options, $depth);
        static::$encodeInitiator = null;

        return $tmp;
    }
}
