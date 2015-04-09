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
    th.no-left, td.no-left {
        border-left: none;
    }
    th.no-right, td.no-right {
        border-right: none;
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

function format($num) {
    return number_format($num, 2);
}

foreach ($scripts as $script) {
    echo "
<h2>$script[filename] (".number_format($script["size"] / 1024, 2)."kB)</h2>
<table class='test'>
<thead>
    <tr>
        <th rowspan='2' class='no-border'></th>
        <th colspan='2'>min</th>
        <th colspan='2'>min + gz</th>
        <th rowspan='2'>time</th>
        <th rowspan='2'>time / mB</th>
    </tr>
    <tr>
        <th class='no-right'>size</th>
        <th class='no-left'>gain</th>
        <th class='no-right'>size</th>
        <th class='no-left'>gain</th>
    </tr>
</thead>
<tbody>";

    foreach ($minifiers as $name => &$minifier) {
        $time = microtime(true);
        try {
            $result = $minifier->run($script["content"]);
        } catch( Exception $e ) {
            echo "<tr>
            <th>$name</th>
            <td colspan='6'>ERROR: ".$e->getMessage()."</td>
            </tr>";
            continue;
        }
        $time = microtime(true) - $time;
        $gzipped = gzencode($result, 9);
        $resultSize = strlen($result);
        $compression = (1 - $resultSize / $script["size"]) * 100;
        $gzippedSize = strlen($gzipped);
        $gzippedCompression = (1 - $gzippedSize / $script["size"]) * 100;
        echo "<tr>
        <th>$name</th>
        <td class='no-right'>".format($resultSize / 1024)."kB</td>
        <td class='no-left'>".format($compression)."%</td>
        <td class='no-right'>".format($gzippedSize / 1024)."kB</td>
        <td class='no-left'>".format($gzippedCompression)."%</td>
        <td>".format($time)."s</td>
        <td>".format($time / ($script["size"] / 1024 / 1024))."s</td>
        </tr>";
    }

    echo "</tbody></table>";
}
