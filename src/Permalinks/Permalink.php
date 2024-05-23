<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Permalinks;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class Permalink
{
    protected int $id;
    protected string $target;
    protected string $slug;
    protected int $count;
    protected int $created;
    protected string $created_by;
    protected int $updated;
    protected string $updated_by;

    public function target(): string
    {
        return $this->target;
    }

    public function url(): URL
    {
        return new URL('/pl:' . $this->slug);
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function created(): DateTime
    {
        return Format::parseDate($this->created);
    }

    public function createdBy(): User
    {
        return Users::get($this->created_by);
    }

    public function updated(): DateTime
    {
        return Format::parseDate($this->updated);
    }

    public function updatedBy(): User
    {
        return Users::get($this->updated_by);
    }

    public function increment(): void
    {
        $this->count++;
        DB::pdo()
            ->exec("UPDATE `permalink` SET `count` = `count` + 1 WHERE `id` = " . $this->id);
    }
}
