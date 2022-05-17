<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DateTime;

abstract class Semesters
{
    const DEFAULT_SEMESTER_DATES = [
        "10" => "January 15",
        "60" => "June 1",
        "80" => "August 15"
    ];

    const SEMESTER_DATES = [
        "2021" => [
            "10" => "January 18",
            "60" => "June 7",
            "80" => "August 23"
        ],
        "2022" => [
            "10" => "January 17",
            "60" => "June 6",
            "80" => "August 22"
        ],
        "2023" => [
            "10" => "January 16",
            "60" => "June 5",
            "80" => "August 21"
        ],
        "2024" => [
            "10" => "January 15",
            "60" => "June 3",
            "80" => "August 19"
        ],
        "2025" => [
            "10" => "January 20",
            "60" => "June 2",
            "80" => "August 18"
        ],
        "2026" => [
            "10" => "January 19",
            "60" => "June 1",
            "80" => "August 17"
        ],
        "2027" => [
            "10" => "January 18",
            "60" => "June 7",
            "80" => "August 23"
        ],
        "2028" => [
            "10" => "January 17",
            "60" => "June 5",
            "80" => "August 21"
        ],
        "2029" => [
            "10" => "January 15",
            "60" => "June 4",
            "80" => "August 20"
        ],
        "2030" => [
            "10" => "January 21",
            "60" => "June 3",
            "80" => "August 19"
        ],
    ];

    const SEMESTER_NAMES = [
        '10' => 'Spring',
        '60' => 'Summer',
        '80' => 'Fall',
    ];

    public static function nextCode(string $code): ?string
    {
        $year = substr($code, 0, 4);
        $code = substr($code, 4, 2);
        if ($code == '80') {
            $code = '10';
            $year = strval(intval($year) + 1);
        } elseif ($code == '60') {
            $code = '80';
        } elseif ($code == '10') {
            $code = '60';
        } else {
            return null;
        }
        return $year . $code;
    }

    public static function dateToString(DateTime $date): string
    {
        return static::codeToString(static::dateToCode($date)) ?? 'Unknown Semester ' . $date->format('Y');
    }

    public static function dateToCode(DateTime $date): string
    {
        $code = [$date->format('Y'), ''];
        $dates = static::SEMESTER_DATES[$code[0]] ?? static::DEFAULT_SEMESTER_DATES;
        // find the latest date that we're currently later than, and pull its code
        foreach (array_reverse($dates) as $c => $d) {
            if ($date->getTimestamp() >= strtotime("$d, $code")) {
                $code[1] .= $c;
                break;
            }
        }
        // if we're earlier than any semesters this year, we must be in the last semester of last year
        if (!$code[1]) {
            $code[0] = strval($date->format('Y') - 1);
            $code[1] = "80";
        }
        return implode('', $code);
    }

    public static function codeToString(string $code): string
    {
        $year = intval(substr($code, 0, 4));
        $sem = static::SEMESTER_NAMES[intval(substr($code, 4, 2))] ?? 'Unknown';
        return $sem . ' ' . $year;
    }

    public static function stringToCode(string $string): ?string
    {
        $string = explode(' ', strtolower($string));
        $code = ['', ''];
        if (in_array('spring', $string)) $code[1] = '10';
        elseif (in_array('summer', $string)) $code[1] = '60';
        elseif (in_array('fall', $string)) $code[1] = '80';
        else return null;
        foreach ($string as $s) {
            if (preg_match('/[0-9]{4}/', $s)) $code[0] = $s;
        }
        if (!$code[0]) return null;
        return implode('', $code);
    }
}
