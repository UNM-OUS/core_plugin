<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DateTime;
use Exception;
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
        if (!isset(Semesters::SEMESTERS[$semester])) throw new Exception("Invalid semester name", 1);
        $this->year = $year;
        $this->semester = Semesters::SEMESTERS[$semester];
    }

    /**
     * @param string|int|null $code
     * @return null|Semester
     * @deprecated use Semesters::fromCode()
     */
    public static function fromCode(string|int|null $code): ?Semester
    {
        return Semesters::fromCode($code);
    }

    /**
     * @param string|null $string
     * @return null|Semester
     * @deprecated use Semesters::fromString()
     */
    public static function fromString(string|null $string): ?Semester
    {
        return Semesters::fromString($string);
    }

    /**
     * @param string|int|DateTime $date
     * @return Semester
     * @deprecated use Semesters::fromDate()
     */
    public static function fromDate($date): Semester
    {
        return Semesters::fromDate($date);
    }

    public function end(): DateTime
    {
        return $this->next()->start()->modify('-1 second');
    }

    public function start(): DateTime
    {
        $date = DateTime::createFromFormat(
            'Y-n-j',
            sprintf(
                '%s-%s-%s',
                $this->year,
                $this->month(),
                $this->day()
            )
        );
        assert($date instanceof DateTime);
        $prelaunch = Semesters::prelaunchInterval();
        if ($prelaunch) {
            if (Semesters::prelaunchInvert()) {
                $date->add($prelaunch);
            } else {
                $date->sub($prelaunch);
            }
        }
        $date->setTime(0, 0, 0, 0);
        return $date;
    }

    public function month(): int
    {
        $c = Semesters::startDate($this->year(), $this->semester());
        if ($c) return $c[0];
        elseif ($this->semester == 10) return Semesters::SPRING_DEFAULT[0];
        elseif ($this->semester == 60) return Semesters::SUMMER_DEFAULT[0];
        else return Semesters::FALL_DEFAULT[0];
    }

    public function year(): int
    {
        return $this->year;
    }

    public function semester(): string
    {
        return @array_flip(Semesters::SEMESTERS)[$this->semester];
    }

    public function day(): int
    {
        $c = Semesters::startDate($this->year(), $this->semester());
        if ($c) return $c[1];
        elseif ($this->semester == 10) return Semesters::SPRING_DEFAULT[1];
        elseif ($this->semester == 60) return Semesters::SUMMER_DEFAULT[1];
        else return Semesters::FALL_DEFAULT[1];
    }

    public function next(int $times = 1): Semester
    {
        if ($times <= 0) return clone $this;
        if ($this->semester == 10) $output = new Semester($this->year, 'Summer');
        elseif ($this->semester == 60) $output = new Semester($this->year, 'Fall');
        else $output = new Semester($this->year + 1, 'Spring');
        return $output->next($times - 1);
    }

    /**
     * @param int $limit
     * @return Generator<int,Semester>
     */
    public function allUpcoming(?int $limit = null): Generator
    {
        $current = $this;
        while ($limit === null or $limit--) yield $current = $current->next();
    }

    /**
     * @param int $limit
     * @return Generator<int,Semester>
     */
    public function allUpcomingFull(?int $limit = null): Generator
    {
        $current = $this;
        while ($limit === null or $limit--) yield $current = $current->nextFull();
    }

    public function nextFull(int $times = 1): Semester
    {
        if ($times <= 0) return clone $this;
        if ($this->semester == 10) $output = new Semester($this->year, 'Fall');
        elseif ($this->semester == 60) $output = new Semester($this->year, 'Fall');
        else $output = new Semester($this->year + 1, 'Spring');
        return $output->nextFull($times - 1);
    }

    /**
     * @param int $limit
     * @return Generator<int,Semester>
     */
    public function allPast(?int $limit = null): Generator
    {
        $current = $this;
        while ($limit === null or $limit--) yield $current = $current->previous();
    }

    public function previous(int $times = 1): Semester
    {
        if ($times <= 0) return clone $this;
        if ($this->semester == 10) $output = new Semester($this->year - 1, 'Fall');
        elseif ($this->semester == 60) $output = new Semester($this->year, 'Spring');
        else $output = new Semester($this->year, 'Summer');
        return $output->previous($times - 1);
    }

    /**
     * @param int $limit
     * @return Generator<int,Semester>
     */
    public function allPastFull(?int $limit = null): Generator
    {
        $current = $this;
        while ($limit === null or $limit--) yield $current = $current->previousFull();
    }

    public function previousFull(int $times = 1): Semester
    {
        if ($times <= 0) return clone $this;
        if ($this->semester == 10) $output = new Semester($this->year - 1, 'Fall');
        elseif ($this->semester == 60) $output = new Semester($this->year, 'Spring');
        else $output = new Semester($this->year, 'Spring');
        return $output->previousFull($times - 1);
    }

    public function intVal(): int
    {
        return ($this->year * 100)
            + Semesters::SEMESTERS[$this->semester()];
    }

    public function __toString()
    {
        return sprintf('%s %s', $this->semester(), $this->year);
    }
}
