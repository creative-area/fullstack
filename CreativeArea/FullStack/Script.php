<?php namespace CreativeArea\FullStack;

/**
 * Class Script.
 */
class Script
{
    /**
     * @param string $block
     *
     * @return string
     */
    private static function indent($block)
    {
        return "    ".str_replace("\n", "\n    ", $block);
    }

    /**
     * @param string $string
     *
     * @throws \CreativeArea\Node\ScriptException
     * @throws \CreativeArea\Node\Exception
     *
     * @return string
     */
    public static function minify($string)
    {
        static $node = null;
        if ($node === null) {
            $node = new \CreativeArea\Node();
        }

        return $node->exec(__DIR__."/minify.js", [], $string)->stdout;
    }

    /**
     * @param array $input
     *
     * @return string|null
     */
    public static function object($input)
    {
        if (!is_array($input) || !count($input)) {
            return;
        }
        $fields = [];
        foreach ($input as $key => $value) {
            if (!$value) {
                continue;
            }
            if (!is_string($value)) {
                $value = json_encode($value);
            }
            $fields[] = json_encode($key).": $value";
        }
        if (!count($fields)) {
            return;
        }

        return "{\n".Script::indent(implode(",\n", $fields))."\n}";
    }

    /**
     * @param string    $args
     * @param string    $body
     * @param bool|null $withCache
     *
     * @return string
     */
    private static function _createFunction($args, $body, $withCache)
    {
        $code = "function($args) {\n".Script::indent(trim($body))."\n}";
        if ($withCache) {
            $code = Script::_createFunction(
                "",
                "\n".
                "var __fsc = {};\n".
                "var __fsf = $code;\n".
                "return (".Script::_createFunction(
                    $args,
                    "\n".
                    "var __fsk = JSON.stringify([$args]);\n".
                    "return __fsc.hasOwnProperty(__fsk) ? __fsc[__fsk] : ( __fsc[__fsk] = __fsf.apply(this,[$args]) );\n",
                    false
                ).");\n",
                false
            )."()";
        }

        return $code;
    }

    /**
     * @param string[]  $args
     * @param string    $body
     * @param bool|null $withCache
     *
     * @return string
     */
    public static function createFunction($args, $body, $withCache = null)
    {
        return Script::_createFunction(implode(", ", $args), $body, $withCache);
    }

    /**
     * @param string $template
     * @param bool   $normalizeSpace
     *
     * @return string
     */
    public static function compileTemplate($template, $normalizeSpace)
    {
        $var = "__fss";
        $code = ["var $var = [];"];
        $split = preg_split("/<%|%>/", $template);
        $currentString = [];
        $isJavascript = true;
        foreach ($split as $part) {
            $isJavascript = !$isJavascript;
            if ($isJavascript) {
                $count = count($currentString);
                $isExpression = (substr($part, 0, 1) == "=");

                if ($isExpression) {
                    $currentString[] = "(".trim(substr($part, 1)).")";
                } else {
                    if ($count>0) {
                        $code[] = "$var.push(".implode(",", $currentString).");";
                    }
                    $code[] = trim($part);
                    $currentString = [];
                }
            } elseif ($part != "") {
                if ($normalizeSpace) {
                    $part = preg_replace("/\s+/", " ", $part);
                }
                $currentString[] = json_encode($part);
            }
        }

        $count = count($currentString);
        if ($count>0) {
            $code[] = "$var.push(".implode(",", $currentString).");";
        }
        $code[] = "return $var.join('');";

        return implode("\n", $code);
    }
}
