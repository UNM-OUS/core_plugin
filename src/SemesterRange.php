<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

class SemesterRange
{
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
        elseif (!$this->start) return $this->end . ' or before';
        elseif (!$this->end) return $this->start . ' or after';
        elseif ($this->end == $this->start->next()) return $this->start . ' or ' . $this->end;
        else return $this->start . ' to ' . $this->end;
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
        elseif ($this->start && $semester->isBefore($this->start)) return false;
        elseif ($this->end && $semester->isAfter($this->end)) return false;
        else return true;
    }
}
