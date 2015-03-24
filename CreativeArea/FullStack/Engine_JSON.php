<?php namespace CreativeArea\FullStack;

/**
 * Trait Engine_JSON.
 */
trait Engine_JSON
{
    private function __construct_json()
    {
    }

    /**
     * @param array $set
     */
    private function findAndConstructObjects(&$set)
    {
        foreach ($set as $key => &$item) {
            if ($key === "____fs" || !is_array($item)) {
                continue;
            }
            $this->findAndConstructObjects($item);
            if (!isset($item[ "____fs" ])) {
                continue;
            }
            $marker = & $item[ "____fs" ];
            $type = $marker[ "type" ];
            $reflectionClass = & $this->classForName->get($type);
            unset($item[ "____fs" ]);
            $object = $reflectionClass->newInstance();
            $object->____fs = & $marker[ "id" ];
            foreach ($item as $name => &$value) {
                $object->$name = & $value;
            }
            if ($reflectionClass->hasMethod("__construct_execution")) {
                $object->__construct_execution();
            }
            $set[ $key ] = & $object;
            $this->addUsedType($type, true);
        }
    }

    /**
     * @param mixed $set
     */
    private function findAndDeconstructObjects(&$set)
    {
        foreach ($set as $key => &$item) {
            if ($key === "____fs") {
                continue;
            }
            $type = gettype($item);
            if ($type !== "object" && $type !== "array") {
                continue;
            }
            $this->findAndDeconstructObjects($item);
            if ($type !== "object") {
                continue;
            }
            $className = get_class($item);
            try {
                $typeName = $this->nameForClass->get($className);
                $reflectionClass = & $this->classForName->get($typeName);
            } catch (Exception $e) {
                continue;
            }
            $new = [
                "____fs" => [
                    "type" => $typeName,
                ],
            ];
            $existed = isset($item->____fs);
            if ($existed) {
                $new[ "____fs" ][ "id" ] = $item->____fs;
            }
            ;
            $annotation = $existed ? "Synchronize" : "Instance";
            foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as &$property) {
                if (!$property->isStatic() && $property->getAnnotation($annotation)) {
                    $name = $property->name;
                    $new[ $name ] = & $item->$name;
                }
            }
            $set[ $key ] = $new;
            $this->addUsedType($typeName);
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
        $tmp = [&$value];
        $this->findAndDeconstructObjects($tmp);

        return json_encode($tmp[0], $options, $depth);
    }
}
