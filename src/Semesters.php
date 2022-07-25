<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\UI\Format;

class Semesters
{
    const SPRING_DEFAULT = [1, 15];
    const SUMMER_DEFAULT = [6, 1];
    const FALL_DEFAULT = [8, 15];
    const SEMESTERS = [
        'Spring' => 10,
        'Summer' => 60,
        'Fall' => 80
    ];

    /**
     * Return the "latest" full semester. In the Summer this is previous Spring.
     *
     * @return Semester
     */
    public static function latestFull(): Semester
    {
        $semester = static::current();
        if ($semester->semester() == 'Summer') return $semester->previousFull();
        else return $semester;
    }

    /**
     * Return the "current" full semester. In the Summer this is upcoming Fall;
     *
     * @return Semester
     */
    public static function currentFull(): Semester
    {
        $semester = static::current();
        if ($semester->semester() == 'Summer') return $semester->nextFull();
        else return $semester;
    }

    public static function current(): Semester
    {
        static $current;
        if (!$current) $current = static::fromDate(time());
        return clone $current;
    }

    /**
     * @param string|int|DateTime $date
     * @return Semester
     */
    public static function fromDate($date): Semester
    {
        $date = Format::parseDate($date)->getTimestamp();
        $year = intval(date('Y', $date));
        $month = intval(date('n', $date));
        $day = intval(date('j', $date));
        if ($month < static::spring($year)[0] || ($month == static::spring($year)[0] && $day < static::spring($year)[1])) {
            // it is still the fall of the previous calendar year
            $year--;
            $semester = 'Fall';
        } elseif ($month < static::summer($year)[0] || ($month == static::summer($year)[0] && $day < static::summer($year)[1])) {
            // it is spring of the current calendar year
            $semester = 'Spring';
        } elseif ($month < static::fall($year)[0] || ($month == static::fall($year)[0] && $day < static::fall($year)[1])) {
            // it is summer of the current calendar year
            $semester = 'Summer';
        } else {
            // it is fall of the current calendar year
            $semester = 'Fall';
        }
        return new Semester($year, $semester);
    }

    public static function spring($year): array
    {
        return Config::get('unm.semesters.' . $year . '.spring')
            ?? static::SPRING_DEFAULT;
    }

    public static function summer($year): array
    {
        return Config::get('unm.semesters.' . $year . '.summer')
            ?? static::SUMMER_DEFAULT;
    }

    public static function fall($year): array
    {
        return Config::get('unm.semesters.' . $year . '.fall')
            ?? static::FALL_DEFAULT;
    }

    /**
     * Sort an array of semesters, works the same way as built-in sort()
     *
     * @param Semester[] $semesters
     * @return bool
     */
    public static function sort(array &$semesters): bool
    {
        return usort($semesters, [static::class, 'compare']);
    }

    /**
     * Function that can be used to compare two Semesters, returning -1, 0, or 1
     * if $a is less than, equal to, or greater than $b, respectively. Used
     * internally by sort()
     *
     * @param Semester $a
     * @param Semester $b
     * @return integer
     */
    public static function compare(Semester $a, Semester $b): int
    {
        if ($a->isEq($b)) return 0;
        elseif ($a->isBefore($b)) return -1;
        else return 1;
    }
}
