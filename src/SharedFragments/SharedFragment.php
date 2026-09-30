<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\SharedFragments;

class SharedFragment
{

    protected int $id;

    protected string $category;

    protected string $name;

    protected string $value;

    protected bool $searchable;

    public function id(): int
    {
        return $this->id;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function searchable(): bool
    {
        return $this->searchable;
    }

    public function tag(string|null $title = null): string
    {
        return sprintf(
            '[%s%s]',
            $this->category(),
            $this->name() ? '="' . $this->name() . '"' : ''
        );
    }

}