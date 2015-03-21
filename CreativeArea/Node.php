<?php namespace CreativeArea;

/**
 * Class Node.
 */
class Node
{
    /**
     * @var string
     */
    private $command;

    /**
     * @param string $binary
     */
    public function __construct($binary = "node")
    {
        $this->command = escapeshellcmd($binary)." ".escapeshellcmd(__DIR__."/Node/exec.js")." ";
    }

    /**
     * @param string   $script
     * @param string[] $args
     * @param string   $input
     *
     * @return \stdClass
     *
     * @throws Node\ScriptException
     * @throws Node\Exception
     */
    public function exec($script, $args = array(), $input = "")
    {
        $command = $this->command." ".escapeshellcmd($script)." ".implode(" ", array_map("escapeshellcmd", $args));

        $process = proc_open($command, array(
            0 => array("pipe", "r"), // stdin
            1 => array("pipe", "w"), // stdout
            2 => array("pipe", "w"), // stderr
        ), $pipes, __DIR__);

        if (is_resource($process)) {
            fwrite($pipes[0], $input);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            proc_close($process);

            if ($error) {
                throw new Node\Exception("unexpected error '$error'");
            }

            $tmp = json_decode($output);

            if (json_last_error()) {
                throw new Node\Exception("cannot decode output '$output'");
            }

            $output = $tmp;

            if (isset($output->exception)) {
                throw new Node\ScriptException($output->exception);
            }

            return $output;
        }
        throw new Node\Exception("cannot open process and execute '$command'");
    }
}
