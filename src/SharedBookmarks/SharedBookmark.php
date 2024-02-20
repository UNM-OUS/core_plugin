<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\SharedBookmarks;

class SharedBookmark
{
    public function __construct(
        protected int $id,
        protected string $category,
        protected string $name,
        protected string $title,
        protected string $url,
    ) {
    }

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

    public function title(): string
    {
        return $this->title;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function tag(string|null $title = null): string
    {
        if ($title) {
            return sprintf(
                '[%s%s]%s[/%s]',
                $this->category,
                $this->name ? '="' . $this->name . '"' : '',
                $title,
                $this->category,
            );
        }
        return sprintf(
            '[%1s%s/]',
            $this->category,
            $this->name ? '="' . $this->name . '"' : '',
        );
    }
}
