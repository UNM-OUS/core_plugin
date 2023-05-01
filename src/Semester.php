<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DateTime;
use DigraphCMS\Config;
use Generator;

class Semester
{
    /** @var int */
    protected $year;
    /** @var int stored as code so that objects can be compared with < and > */
    protected $semester;

    public function __construct(int $year, string $semester)
    {
        $semester = ucfirst(trim(strtolower($semester)));
        if (!isset(Semesters::SEMESTERS[$semester])) throw new \Exception("Invalid semester name", 1);
        $this->year = $year;
        $this->semester = Semesters::SEMESTERS[$semester];
    }

    public static function fromString(string $string): ?Semester
    {
        $string = trim($string);
        if (preg_match('/^(spring|summer|fall) ([0-9]{4})$/i', $string, $m)) {
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
        if (!$year) return null;
        $semester = @array_flip(Semesters::SEMESTERS)[$code - $year * 100];
        if (!$semester) return null; // @phpstan-ignore-line
        if ($year < 1000 || $year > 9999) return null;
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
        )
        )->setTime(0, 0, 0, 0);
    }

    public function end(): DateTime
    {
        return $this->next()->start()->modify('-1 second');
    }

    /**
     * @param int $limit
     * @return Generator<int,Semester>
     */
    public function allUpcoming(int $limit = null): Generator
    {
        $current = $this;
        while ($limit === null or $limit--) yield $current = $current->next();
    }

    /**
     * @param int $limit
     * @return Generator<int,Semester>
     */
    public function allUpcomingFull(int $limit = null): Generator
    {
        $current = $this;
        while ($limit === null or $limit--) yield $current = $current->nextFull();
    }

    /**
     * @param int $limit
     * @return Generator<int,Semester>
     */
    public function allPast(int $limit = null): Generator
    {
        $current = $this;
        while ($limit === null or $limit--) yield $current = $current->previous();
    }

    /**
     * @param int $limit
     * @return Generator<int,Semester>
     */
    public function allPastFull(int $limit = null): Generator
    {
        $current = $this;
        while ($limit === null or $limit--) yield $current = $current->previousFull();
    }

    public function next(int $times = 1): Semester
    {
        if ($times <= 0) return clone $this;
        if ($this->semester == 10) $output = new Semester($this->year, 'Summer');
        elseif ($this->semester == 60) $output = new Semester($this->year, 'Fall');
        else $output = new Semester($this->year + 1, 'Spring');
        return $output->next($times - 1);
    }

    public function nextFull(int $times = 1): Semester
    {
        if ($times <= 0) return clone $this;
        if ($this->semester == 10) $output = new Semester($this->year, 'Fall');
        elseif ($this->semester == 60) $output = new Semester($this->year, 'Fall');
        else $output = new Semester($this->year + 1, 'Spring');
        return $output->nextFull($times - 1);
    }

    public function previous(int $times = 1): Semester
    {
        if ($times <= 0) return clone $this;
        if ($this->semester == 10) $output = new Semester($this->year - 1, 'Fall');
        elseif ($this->semester == 60) $output = new Semester($this->year, 'Spring');
        else $output = new Semester($this->year, 'Summer');
        return $output->previous($times - 1);
    }

    public function previousFull(int $times = 1): Semester
    {
        if ($times <= 0) return clone $this;
        if ($this->semester == 10) $output = new Semester($this->year - 1, 'Fall');
        elseif ($this->semester == 60) $output = new Semester($this->year, 'Spring');
        else $output = new Semester($this->year, 'Spring');
        return $output->previousFull($times - 1);
    }

    public function month(): int
    {
        if ($c = Config::get('unm.semesters.' . $this->year . '.' . strtolower($this->semester()))) return $c[0];
        elseif ($this->semester == 10) return Semesters::SPRING_DEFAULT[0];
        elseif ($this->semester == 60) return Semesters::SUMMER_DEFAULT[0];
        else return Semesters::FALL_DEFAULT[0];
    }

    public function day(): int
    {
        if ($c = Config::get('unm.semesters.' . $this->year . '.' . strtolower($this->semester()))) return $c[1];
        elseif ($this->semester == 10) return Semesters::SPRING_DEFAULT[1];
        elseif ($this->semester == 60) return Semesters::SUMMER_DEFAULT[1];
        else return Semesters::FALL_DEFAULT[1];
    }

    public function intVal(): int
    {
        return ($this->year * 100)
            + Semesters::SEMESTERS[$this->semester()];
    }

    public function year(): int
    {
        return $this->year;
    }

    public function semester(): string
    {
        return @array_flip(Semesters::SEMESTERS)[$this->semester];
    }

    public function __toString()
    {
        return sprintf('%s %s', $this->semester(), $this->year);
    }
}