<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DateTime;
use DigraphCMS\Config;
use Generator;

class Semester
{
    protected $year, $semester;

    public function __construct(int $year, string $semester)
    {
        $semester = ucfirst(trim(strtolower($semester)));
        if (!isset(Semesters::SEMESTERS[$semester])) throw new \Exception("Invalid semester name", 1);
        $this->year = $year;
        $this->semester = $semester;
    }

    public static function fromString(string $string): ?Semester
    {
        if (preg_match('/(spring|summer|fall) ([0-9]{4})/i', $string, $m)) {
            return new Semester($m[2], $m[1]);
        } else return null;
    }

    /**
     * @param string|int|DateTime $date
     * @return Semester
     */
    public static function fromDate($date): Semester
    {
        return Semesters::fromDate($date);
    }

    public static function fromCode($code): ?Semester
    {
        $code = intval($code);
        $year = floor($code / 100);
        $semester = @array_flip(Semesters::SEMESTERS)[$code - $year * 100];
        if (!$year || !$code) return null;
        else return new Semester($year, $semester);
    }

    public function start(): DateTime
    {
        return (DateTime::createFromFormat(
            'Y-n-j',
            sprintf(
                '%s-%s-%s',
                $this->year,
                $this->month(),
                $this->day()
            )
        ))->setTime(0, 0, 0, 0);
    }

    public function end(): DateTime
    {
        return $this->next()->start()->modify('-1 second');
    }

    public function allUpcoming(int $limit = null): Generator
    {
        $current = $this;
        while ($limit === null or $limit--) yield $current = $current->next();
    }

    public function allUpcomingFull(int $limit = null): Generator
    {
        $current = $this;
        while ($limit === null or $limit--) yield $current = $current->nextFull();
    }

    public function allPast(int $limit = null): Generator
    {
        $current = $this;
        while ($limit === null or $limit--) yield $current = $current->previous();
    }

    public function allPastFull(int $limit = null): Generator
    {
        $current = $this;
        while ($limit === null or $limit--) yield $current = $current->previousFull();
    }

    public function next(): Semester
    {
        if ($this->semester == 'Spring') return new Semester($this->year, 'Summer');
        elseif ($this->semester == 'Summer') return new Semester($this->year, 'Fall');
        else return new Semester($this->year + 1, 'Spring');
    }

    public function nextFull(): Semester
    {
        if ($this->semester == 'Spring') return new Semester($this->year, 'Fall');
        elseif ($this->semester == 'Summer') return new Semester($this->year, 'Fall');
        else return new Semester($this->year + 1, 'Spring');
    }

    public function previous(): Semester
    {
        if ($this->semester == 'Spring') return new Semester($this->year - 1, 'Fall');
        elseif ($this->semester == 'Summer') return new Semester($this->year, 'Spring');
        else return new Semester($this->year, 'Summer');
    }

    public function previousFull(): Semester
    {
        if ($this->semester == 'Spring') return new Semester($this->year - 1, 'Fall');
        elseif ($this->semester == 'Summer') return new Semester($this->year, 'Spring');
        else return new Semester($this->year, 'Spring');
    }

    public function month(): int
    {
        if ($c = Config::get('unm.semesters.' . $this->year . '.' . strtolower($this->semester))) return $c[0];
        elseif ($this->semester == 'Spring') return Semesters::SPRING_DEFAULT[0];
        elseif ($this->semester == 'Summer') return Semesters::SUMMER_DEFAULT[0];
        else return Semesters::FALL_DEFAULT[0];
    }

    public function day(): int
    {
        if ($c = Config::get('unm.semesters.' . $this->year . '.' . strtolower($this->semester))) return $c[1];
        elseif ($this->semester == 'Spring') return Semesters::SPRING_DEFAULT[1];
        elseif ($this->semester == 'Summer') return Semesters::SUMMER_DEFAULT[1];
        else return Semesters::FALL_DEFAULT[1];
    }

    public function intVal(): int
    {
        return $this->year * 100
            + Semesters::SEMESTERS[$this->semester];
    }

    public function year(): int
    {
        return $this->year;
    }

    public function semester(): string
    {
        return $this->semester;
    }

    public function isEq(Semester $semester): bool
    {
        return $this->year == $semester->year()
            && $this->semester = $semester->semester();
    }

    public function isBefore(Semester $semester): bool
    {
        return $this->intVal() < $semester->intVal();
    }

    public function isAfter(Semester $semester): bool
    {
        return $this->intVal() > $semester->intVal();
    }

    public function __toString()
    {
        return sprintf('%s %s', $this->semester, $this->year);
    }
}