<?php namespace CreativeArea\FullStack;

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
     * @throws Exception
     *
     * @return string
     */
    public static function minify($string)
    {
        $process = proc_open("node ".__DIR__."/minify.js", array(
            0 => array("pipe", "r"), // stdin
            1 => array("pipe", "w"), // stdout
            2 => array("pipe", "w"), // stderr
        ), $pipes, __DIR__);

        if (is_resource($process)) {
            fwrite($pipes[0], $string);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            proc_close($process);

            if ($error) {
                try {
                    $info = json_decode($error);
                    $json_error = json_last_error();
                } catch (\Exception $e) {
                    $json_error = true;
                }
                if ($json_error) {
                    throw new Exception($error);
                }
                // TODO: some kind of source map
                throw new Exception($info->message);
            }

            return $output;
        }
        throw new Exception("cannot open process and use minify");
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
        $fields = array();
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

        return "({\n".Script::indent(implode(",\n", $fields))."\n})";
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
                $args,
                "\n".
                "var ____fsk = JSON.stringify([$args]);\n".
                "if (!____fsc.hasOwnProperty(____fsk)) ____fsc[____fsk] = ($code).apply(this,arguments);\n".
                "return ____fsc[____fsk];\n",
                false
            );
            $code = "(".Script::_createFunction(
                "",
                "\n".
                "var ____fsc = {};\n".
                "return ($code);\n",
                false
            ).")()";
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
        $code = array("var $var = [];");
        $split = preg_split("/<%|%>/", $template);
        $currentString = array();
        $isJavascript = true;
        foreach ($split as $part) {
            $isJavascript = !$isJavascript;
            if ($isJavascript) {
                $count = count($currentString);
                $isExpression = (substr($part, 0, 1) == "=");

                if ($isExpression) {
                    array_push($currentString, "(".trim(substr($part, 1)).")");
                } else {
                    if ($count>0) {
                        array_push($code, "$var.push(".implode(",", $currentString).");");
                    }
                    array_push($code, trim($part));
                    $currentString = array();
                }
            } elseif ($part != "") {
                if ($normalizeSpace) {
                    $part = preg_replace("/\s+/", " ", $part);
                }
                array_push($currentString, json_encode($part));
            }
        }

        $count = count($currentString);
        if ($count>0) {
            array_push($code, "$var.push(".implode(",", $currentString).");");
        }
        array_push($code, "return $var.join('');");

        return implode("\n", $code);
    }
}
