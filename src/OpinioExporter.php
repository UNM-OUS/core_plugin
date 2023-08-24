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
        $row = implode(
            ',',
            array_map(
                function ($cell): string {
                    $cell = static::transliterate("$cell");
                    $cell = str_replace("\r", " ", $cell);
                    $cell = str_replace("\n", " ", $cell);
                    $cell = str_replace('"', "'", $cell);
                    $cell = str_replace(',', "", $cell);
                    return $cell;
                },
                $row
            )
        );
        // @phpstan-ignore-next-line
        return iconv(
            // @phpstan-ignore-next-line
            mb_detect_encoding($row),
            'ASCII//TRANSLIT//IGNORE',
            $row
        );
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
            $output .= "\n";
        }
        return $output;
    }

    protected static function transliterate(string $string): string
    {
        $string = strtr(
            $string,
            mb_convert_encoding(
                'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ',
                'ASCII'
            ),
            'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy'
        );
        return $string;
    }
}