<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use Stringable;
use URLify;

class OpinioExporter
{
    /**
     * @param array<mixed,int|number|string|Stringable> $row
     * @return string
     */
    public static function row(array $row): string
    {
        $row = array_map(
            function ($cell): string {
                $cell = URLify::transliterate("$cell");
                $cell = str_replace("\r", " ", $cell);
                $cell = str_replace("\n", " ", $cell);
                $cell = str_replace('"', "'", $cell);
                $cell = str_replace(',', "", $cell);
                return $cell;
            },
            $row
        );
        return implode(',', $row);
    }

    /**
     * @param array<mixed,array<mixed,int|number|string|Stringable>> $input
     * @param boolean $auto_headers
     * @return string
     */
    public static function array(array $input, bool $auto_headers = false): string
    {
        $output = '';
        if ($auto_headers && $input) {
            array_unshift($input, array_keys(reset($input)));
        }
        foreach ($input as $row) {
            $output .= static::row($row);
            $output .= PHP_EOL;
        }
        return $output;
    }
}