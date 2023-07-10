<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Pagination\AbstractColumnFilteringHeader;
use Envms\FluentPDO\Queries\Select as QueriesSelect;

class ColumnSemesterFilteringHeader extends AbstractColumnFilteringHeader
{
    /** @var bool */
    protected $summers;
    /** @var Semester */
    protected $startSemester, $endSemester;

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

    /**
     * @return FormWrapper
     */
    public function toolbox()
    {
        $form = $this->form();

        // try to determine start and end semesters automatically
        $query = $this->section ? clone $this->section->rawSource() : null;
        if ($query instanceof AbstractMappedSelect) $query = clone $query->query();
        if ($query instanceof QueriesSelect) {
            $query->asObject(false);
            $lowest = clone $query;
            // @phpstan-ignore-next-line
            $lowest = @$lowest->limit(1)->offset(0)
                ->select(AbstractMappedSelect::parseJsonRefs($this->column()) . ' AS semfilter_column', true)
                // @phpstan-ignore-next-line
                ->order(null)
                ->order(AbstractMappedSelect::parseJsonRefs($this->column()) . ' ASC')
                ->fetchAll()[0]['semfilter_column'];
            $this->startSemester = $lowest ? Semesters::fromCode($lowest) : $this->startSemester;
            $highest = clone $query;
            // @phpstan-ignore-next-line
            $highest = @$highest->limit(1)->offset(0)
                ->select(AbstractMappedSelect::parseJsonRefs($this->column()) . ' AS semfilter_column', true)
                // @phpstan-ignore-next-line
                ->order(null)
                ->order(AbstractMappedSelect::parseJsonRefs($this->column()) . ' DESC')
                ->fetchAll()[0]['semfilter_column'];
            $this->endSemester = $highest ? Semesters::fromCode($highest) : $this->endSemester;
        }

        // build up options list
        $options = [];
        $semester = clone $this->startSemester;
        for ($i = 0; $i < 50; $i++) {
            $options[$semester->intVal()] = $semester->__toString();
            if ($semester >= $this->endSemester) break;
            $semester = $this->summers
                ? $semester->next()
                : $semester->nextFull();
        }

        $start = (new Field('Start', new SELECT($options)))
            ->setID('start')
            ->setDefault(@$this->config()['start'] ?? $this->startSemester->intVal())
            ->addForm($form);

        $end = (new Field('End', new SELECT($options)))
            ->setID('end')
            ->setDefault(@$this->config()['end'] ?? $this->endSemester->intVal())
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

    /**
     * @return array<mixed,string>
     */
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

    /**
     * @return array<mixed,array<int,string|mixed[]>>
     */
    public function getWhereClauses(): array
    {
        $clauses = [];
        if ($this->config('start')) $clauses[] = [$this->column() . ' >= ?', [$this->config('start')]];
        if ($this->config('end')) $clauses[] = [$this->column() . ' <= ?', [$this->config('end')]];
        return $clauses;
    }

    /**
     * @return array<mixed,string>
     */
    public function getJoinClauses(): array
    {
        return [];
    }

    /**
     * @return array<mixed,array<int,string|mixed[]>>
     */
    public function getLikeClauses(): array
    {
        return [];
    }
}