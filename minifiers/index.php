<style>
    body {
        font-family: Tahoma;
    }
    table {
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid black;
    }
    table.test {
        margin-left: 2em;
    }
    th, td {
        padding: 1em;
    }
    th.no-border {
        border: none;
    }
</style>
<h1>Minifier Test</h1>
<?php

set_include_path(implode(PATH_SEPARATOR, [get_include_path(), realpath(__DIR__."/..")]));
spl_autoload_register();
require_once __DIR__."/../vendor/autoload.php";

$minifiers = [];

$dir = dir(__DIR__."/Minifier");
while (false !== ($filename = $dir->read())) {
    if (preg_match("/\\.php$/", $filename)) {
        $name = preg_replace("/\\.php$/", "", $filename);
        $minifiers[$name] = (new ReflectionClass("Minifier\\$name"))->newInstance();
    }
}

echo "
<table><tbody>";

foreach ($minifiers as $name => &$minifier) {
    echo "
    <tr>
    <th>$name</th>
    <td>$minifier->environment</td>
    </tr>";
}

echo "</tbody></table>";

$scripts = [];

$dir = dir(__DIR__."/scripts");
while (false !== ($filename = $dir->read())) {
    if (preg_match("/\\.js$/", $filename)) {
        $content = file_get_contents(__DIR__."/scripts/$filename");
        $scripts[] = [
            "filename" => $filename,
            "content" => $content,
            "size" => strlen($content),
        ];
    }
}

usort($scripts, function ($a, $b) {
    return $a["size"] - $b["size"];
});

foreach ($scripts as $script) {
    echo "
    <h2>$script[filename] (".number_format($script["size"] / 1024, 2)."kB)</h2>
    <table class='test'><thead><th class='no-border'></th><th>size</th><th>gain</th><th>time</th><th>time / mB</th></thead><tbody>";

    foreach ($minifiers as $name => &$minifier) {
        $time = microtime(true);
        $result = $minifier->run($script["content"]);
        $time = microtime(true) - $time;
        $resultSize = strlen($result);
        echo "<tr>
        <th>$name</th>
        <td>".number_format($resultSize / 1024, 2)."kB</td>
        <td>".number_format((1 - $resultSize / $script["size"]) * 100, 2)."%</td>
        <td>".number_format($time, 2)."s</td>
        <td>".number_format($time / ($script["size"] / 1024 / 1024), 2)."s</td>
        </tr>";
    }

    echo "</tbody></table>";
}
