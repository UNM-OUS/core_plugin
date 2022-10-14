<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\AbstractColumnFilteringHeader;
use Envms\FluentPDO\Queries\Select as QueriesSelect;

class ColumnSemesterFilteringHeader extends AbstractColumnFilteringHeader
{
    protected $summers, $startSemester, $endSemester;

    /**
     * $offsetStart and $offsetEnd are ignored if a query can be retrieved from
     * the paginated section and used to automatically set them.
     *
     * @param string $label
     * @param string $column
     * @param boolean $summers
     * @param integer $offsetStart
     * @param integer $offsetEnd
     */
    public function __construct(string $label, string $column, bool $summers = true, $offsetStart = 10, $offsetEnd = 10)
    {
        $this->summers = $summers;
        $this->startSemester = $summers
            ? Semesters::current()->previous($offsetStart)
            : Semesters::current()->previousFull($offsetStart);
        $this->endSemester = $summers
            ? Semesters::current()->next($offsetEnd)
            : Semesters::current()->nextFull($offsetEnd);
        parent::__construct($label, $column);
    }

    public function toolbox()
    {
        $form = $this->form();

        // try to determine start and end semesters automatically
        $query = $this->section ? clone $this->section->source() : null;
        if ($query instanceof AbstractMappedSelect) $query = clone $query->query();
        if ($query instanceof QueriesSelect) {
            $query->asObject(false);
            $lowest = clone $query;
            $lowest = @$lowest->limit(1)->offset(0)
                ->select($this->column() . ' AS semfilter_column', true)
                ->order(null)->order($this->column() . ' ASC')
                ->fetchAll()[0]['semfilter_column'];
            $this->startSemester = $lowest ? Semester::fromCode($lowest) : $this->startSemester;
            $highest = clone $query;
            $highest = @$highest->limit(1)->offset(0)
                ->select($this->column() . ' AS semfilter_column', true)
                ->order(null)->order($this->column() . ' ASC')
                ->fetchAll()[0]['semfilter_column'];
            $this->endSemester = $highest ? Semester::fromCode($highest) : $this->endSemester;
        }

        // build up options list
        $options = [];
        $semester = clone $this->startSemester;
        for ($i = 0; $i < 50; $i++) {
            $options[$semester->intVal()] = $semester->__toString();
            if (!$semester->isBefore($this->endSemester)) break;
            $semester = $this->summers
                ? $semester->next()
                : $semester->nextFull();
        }

        $start = (new Field('Start', new SELECT($options)))
            ->setID('start')
            ->setDefault(@$this->config()['start'] ? Format::parseDate($this->config()['start']) : null)
            ->addForm($form);

        $end = (new Field('End date', new SELECT($options)))
            ->setID('end')
            ->setDefault(@$this->config()['end'] ? Format::parseDate($this->config()['end']) : null)
            ->addForm($form);

        $end->addValidator(function () use ($start, $end) {
            if ($end->value() < $start->value()) return 'End semester cannot be before the start semester';
            else return null;
        });

        $sort = (new Field('Sorting', new SELECT([
            false => 'None',
            'ASC' => 'Oldest first',
            'DESC' => 'Newest first'
        ])))
            ->setID('sort')
            ->setDefault(@$this->config()['sort'])
            ->addForm($form);

        $form->addCallback(function () use ($start, $end, $sort) {
            $config = [];
            if ($start->value()) $config['start'] = $start->value();
            if ($end->value()) $config['end'] = $end->value();
            if ($sort->value()) $config['sort'] = $sort->value();
            throw new RedirectException($this->url($config ? $config : null));
        });

        return $form;
    }

    public function getOrderClauses(): array
    {
        switch (@$this->config()['sort']) {
            case 'ASC':
                return [
                    'CASE WHEN ' . $this->column() . ' IS NULL THEN 0 ELSE 1 END',
                    $this->column() . ' ASC'
                ];
            case 'DESC':
                return [
                    'CASE WHEN ' . $this->column() . ' IS NULL THEN 1 ELSE 0 END',
                    $this->column() . ' DESC'
                ];
            default:
                return [];
        }
    }

    public function getWhereClauses(): array
    {
        $clauses = [];
        if ($this->config('start')) $clauses[] = [$this->column() . ' >= ?', [$this->config('start')]];
        if ($this->config('end')) $clauses[] = [$this->column() . ' <= ?', [$this->config('end')]];
        return $clauses;
    }

    public function getJoinClauses(): array
    {
        return [];
    }
}
