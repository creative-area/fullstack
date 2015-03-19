<?php namespace CreativeArea\Annotate;

/**
 * Class Annotations.
 */
class Annotations
{
    public static $definitionTypes = array(
        "bool" => "bool",
        "object" => "object",
        "string" => "string",
        "string[]" => "array_of_strings",
    );

    /**
     * @var array
     */
    private $cache = array();

    /**
     * @param string           $comment
     * @param (string|array)[] $definitions
     *
     * @throws Exception
     */
    public function __construct($comment, &$definitions)
    {
        if ($comment) {
            $matches = array();
            preg_match_all(
                '/@([A-Z][a-zA-Z0-9]*)((?:[^"\)\n]|(?:"(?:\\"|[^"\n])*"))*)/',
                $comment,
                $matches,
                PREG_SET_ORDER
            );
            foreach ($matches as $match) {
                $type = $match[ 1 ];
                $value = $match[ 2 ];
                if (!isset($definitions[ $type ])) {
                    throw new Exception("unknown annotation $type");
                }
                $definition = & $definitions[ $type ];
                $value = $this->decodeStringValue($value);
                if (is_array($definition)) {
                    $this->add_object($type, $value, $definition);
                } else {
                    $method = "add_".Annotations::$definitionTypes[ $definition ];
                    $this->$method($type, $value);
                }
            }
        }
    }

    /**
     * @param $string
     *
     * @return mixed
     */
    private function decodeStringValue($string)
    {
        if ($string && ($string = trim($string))) {
            $value = json_decode($string);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $value = $string;
            }
        } else {
            $value = null;
        }

        return $value;
    }

    /**
     * @param string $type
     * @param null   $value
     *
     * @throws Exception
     */
    private function add_bool($type, $value)
    {
        if (isset($this->cache[ $type ])) {
            throw new Exception("annotation $type cannot be used twice");
        }
        if ($value !== null) {
            throw new Exception("annotation $type does not accept any value");
        }
        $this->cache[ $type ] = true;
    }

    /**
     * @param string        $type
     * @param stdClass|null $value
     * @param array         $default
     *
     * @throws Exception
     */
    private function add_object($type, $value, &$default = array())
    {
        if (isset($this->cache[ $type ])) {
            throw new Exception("annotation $type cannot be used twice");
        }
        if ($value === null) {
            $value = new stdClass();
        }
        if (!is_object($value)) {
            throw new Exception("annotation $type only accepts an object");
        }
        $this->cache[ $type ] = & $value;
        foreach ($default as $field => $defaultValue) {
            if (!property_exists($value, $field)) {
                $value->$field = $defaultValue;
            }
        }
    }

    /**
     * @param string $type
     * @param string $value
     *
     * @throws Exception
     */
    private function add_string($type, $value)
    {
        if (isset($this->cache[ $type ])) {
            throw new Exception("annotation $type cannot be used twice");
        }
        if (!is_string($value)) {
            throw new Exception("annotation $type only accepts a string");
        }
        $this->cache[ $type ] = $value;
    }

    /**
     * @param string   $type
     * @param string[] $value
     *
     * @throws Exception
     */
    private function add_array_of_strings($type, $value)
    {
        if (is_string($value)) {
            $value = array( $value );
        }
        if (!is_array($value)) {
            throw new Exception("annotation $type only accepts a string or a list of strings");
        }
        if (!isset($this->cache[ $type ])) {
            $this->cache[ $type ] = array();
        }
        foreach ($value as $string) {
            if (!is_string($string)) {
                throw new Exception("annotation $type only accepts a string or a list of strings");
            }
            $this->cache[ $type ][] = $string;
        }
    }

    /**
     * @param string $type
     *
     * @return null|mixed
     */
    public function get($type)
    {
        return isset($this->cache[ $type ]) ? $this->cache[ $type ] : null;
    }
}
