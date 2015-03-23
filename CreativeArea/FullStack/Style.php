<?php namespace CreativeArea\FullStack;

/**
 * Class Style.
 */
class Style
{
    /**
     * @param string[] $paths
     *
     * @return string
     */
    private static function filesToImport(&$paths)
    {
        $output = "";
        foreach ($paths as $path) {
            $output .= is_array($path) ? "$path[0]\n" : "@import ".json_encode($path).";\n";
        }

        return $output;
    }

    /**
     * @param string[] $parent
     * @param string[] $own
     * @param callable $includeCallback
     *
     * @return string
     *
     * @throws \CreativeArea\FileFinder\Exception
     */
    public static function compile(&$parent, &$own, $includeCallback)
    {
        $scss = new \scssc();
        $scss->setImportPaths(["", $includeCallback]);
        $scss->setFormatter("scss_formatter_compressed");
        $compiled = $scss->compile(
            Style::filesToImport($parent).
            ".marker{----fs:0}\n".
            Style::filesToImport($own)
        );

        return preg_replace("/^.*----fs[^}]+}/", "", $compiled);
    }
}
