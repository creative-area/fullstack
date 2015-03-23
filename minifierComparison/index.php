<style>
    body {
        font-family: Tahoma;
    }
    table {
        border-collapse: collapse;
    }
    table, th, td {
        border: 1px solid black;
    }
    table.test {
        margin-left: 2em;
    }
    th, td {
        padding: 1em;
    }
</style>
<?php

require_once __DIR__."/include/include.php";

echo "<h1>Minifier Test</h1>";

$minifiers = [
    "mrclay/minify" => [
        "name" => "Minify",
        "language" => "PHP",
        "github" => "1473 stars, 406 commits (latest Oct 30 2014)",
        "minify" => function($code) {
                return JSMin::minify($code);
            }
    ],
    "mishoo/UglifyJS2" => [
        "name" => "UglifyJS 2",
        "language" => "JavaScript",
        "github" => "3159 stars, 627 commits (latest Mar 22, 2015)",
        "minify" => function($code) {
                return \CreativeArea\FullStack\Script::minify($code);
            }
    ],
];

echo "
<table><thead><tr><th>name</th><th>language</th><th>github</th><th></th></tr><tbody>";

foreach($minifiers as $id => $minifier) {
    echo "
    <tr>
    <td>$minifier[name]</td>
    <td>$minifier[language]</td>
    <td><a target='_blank' href='https://github.com/$id'>https://github.com/$id</a></td>
    <td>$minifier[github]</td>
    </tr>";
}

echo "</tbody></table>";

foreach([
    "jquery-jsonp-2.4.0.js",
    "es6-promise-2.0.1.js",
    "qunit-1.17.1.js",
    "jquery-1.11.2.js",
    "lodash-3.5.0.js",
] as $sourceFile) {
    $source = file_get_contents(__DIR__."/js/$sourceFile");
    $sourceSize = strlen($source);

    echo "
    <h2>$sourceFile (" . number_format( $sourceSize / 1024, 2 ) . "kB)</h2>
    <table class='test'><thead><th>Minifer</th><th>size</th><th>gain</th><th>time</th><th>time / mB</th></thead><tbody>";

    foreach($minifiers as $minifier) {
        $time = microtime(true);
        $result = $minifier[ "minify" ]->__invoke($source);
        $time = microtime(true) - $time;
        $resultSize = strlen($result);
        echo "<tr>
        <td>$minifier[name]</td>
        <td>" . number_format( $resultSize / 1024, 2). "kB</td>
        <td>" . number_format( (1 - $resultSize / $sourceSize) * 100, 2 ) . "%</td>
        <td>" . number_format( $time, 2 ) . "s</td>
        <td>" . number_format( $time / ( $sourceSize / 1024 / 1024 ), 2 ) . "s</td>
        </tr>";
    }

    echo "</tbody></table>";
}
