<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

class SemesterRange
{
    /** @var Semester|null */
    protected $start, $end;

    /**
     * Entering null for the start or end makes this range extend indefinitely,
     * for example entering null for $start would make all semesters up to $end
     * match, and leaving both start and end null would make a range containing
     * all semesters.
     *
     * @param Semester|null $start
     * @param Semester|null $end
     */
    public function __construct(?Semester $start, ?Semester $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function __toString()
    {
        if (!$this->start && !$this->end) return 'any semester';
        elseif (!$this->start) return $this->end . ' or earlier';
        elseif (!$this->end) return $this->start . ' or later';
        elseif ($this->end == $this->start->next()) {
            if ($this->start->year() == $this->end->year()) return sprintf('%s and %s %s', $this->start->semester(), $this->end->semester(), $this->end->year());
            else return $this->start . ' and ' . $this->end;
        } else {
            if ($this->start == $this->end) return $this->start->__toString();
            elseif ($this->start->year() == $this->end->year()) return sprintf('%s to %s %s', $this->start->semester(), $this->end->semester(), $this->end->year());
            else return $this->start . ' to ' . $this->end;
        }
    }

    public function start(): ?Semester
    {
        return $this->start;
    }

    public function end(): ?Semester
    {
        return $this->end;
    }

    public function contains(?Semester $semester): bool
    {
        if (!$semester) return false;
        elseif ($this->start && $semester < $this->start) return false;
        elseif ($this->end && $semester > $this->end) return false;
        else return true;
    }
}