<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;

/**
 * @method Semester|null value(bool $useDefault = false)
 * @method Semester|null default()
 * @method $this setDefault(Semester|null $default)
 * @method SELECT input()
 */
class SemesterField extends Field
{
    public function __construct(string $label, int $startOffset = 0, int $count = 10, bool $summers = false)
    {
        if ($summers) $first = Semesters::current();
        else $first = Semesters::latestFull();
        // use startOffset to move starting point forward/backward as needed
        if ($startOffset < 0) do {
                $first = $summers ? $first->previous() : $first->previousFull();
            } while (++$startOffset);
        if ($startOffset > 0) do {
                $first = $summers ? $first->next() : $first->nextFull();
            } while (--$startOffset);
        // set up field with options
        $field = new SELECT();
        $field->setOption($first->intVal(), $first->__toString());
        foreach ($summers ? $first->allUpcoming($count - 1) : $first->allUpcomingFull($count - 1) as $semester) {
            $field->setOption($semester->intVal(), $semester->__toString());
        }
        parent::__construct($label, $field);
    }

    public function value(bool $useDefault = false): mixed
    {
        return ($value = parent::value($useDefault))
            ? Semesters::fromCode($value)
            : null;
    }

    public function default(): ?Semester
    {
        return ($value = parent::default())
            ? Semesters::fromCode($value)
            : null;
    }

    public function setDefault(mixed $default): static
    {
        parent::setDefault($default ? $default->intVal() : null);
        return $this;
    }
}