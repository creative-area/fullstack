<?php namespace CreativeArea\FullStack;

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
            $output .= "@import ".json_encode($path).";\n";
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
        $scss->setImportPaths(array("", $includeCallback));
        $scss->setFormatter("scss_formatter_compressed");
        $compiled = $scss->compile(
            Style::filesToImport($parent).
            ".marker{----fullstack:0}\n".
            Style::filesToImport($own)
        );
        return preg_replace("/^.*----fullstack[^}]+}/", "", $compiled);
    }
}
