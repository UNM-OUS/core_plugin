<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB;

use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\DB\DB;

/** @extends AbstractMappedSelect<SiteAlert> */
class SiteAlerts extends AbstractMappedSelect
{
    const DB_CLASS = DB::class;
    const DB_TABLE = 'site_alerts';
    protected $returnObjectClass = SiteAlert::class;

    public static function new(): SiteAlerts
    {
        return new SiteAlerts(
            static::DB_CLASS::query()->from(static::DB_TABLE)
        );
    }

    public static function get(null|string $uuid): ?SiteAlert
    {
        if (!$uuid) return null;
        return static::new()->where('uuid', $uuid)->fetch();
    }

    public function currentAlerts(): static
    {
        return $this
            ->where('(start_time is null OR start_time <= ?)', [time()])
            ->where('(end_time is null OR end_time > ?)', [time()]);
    }

    public function upcoming(): static
    {
        return $this
            ->where('start_time is not null')
            ->where('start_time > ?', [time()]);
    }

    public function past(): static
    {
        return $this
            ->where('end_time is not null')
            ->where('end_time <= ?', [time()]);
    }

    public static function update(SiteAlert $alert): void
    {
        static::DB_CLASS::query()->update(
            static::DB_TABLE,
            [
                'title' => $alert->title(),
                'content' => $alert->contentSource(),
                'class' => $alert->class(),
                'start_time' => $alert->start()?->getTimestamp(),
                'end_time' => $alert->end()?->getTimestamp(),
            ]
        )
            ->where('uuid', $alert->uuid())
            ->execute();
    }

    public static function create(SiteAlert $alert): void
    {
        static::DB_CLASS::query()->insertInto(
            static::DB_TABLE,
            [
                'uuid' => $alert->uuid(),
                'title' => $alert->title(),
                'content' => $alert->contentSource(),
                'class' => $alert->class(),
                'start_time' => $alert->start()?->getTimestamp(),
                'end_time' => $alert->end()?->getTimestamp(),
            ]
        )
            ->execute();
    }

    public static function delete(SiteAlert $alert): void
    {
        static::DB_CLASS::query()->delete(static::DB_TABLE)
            ->where('uuid', $alert->uuid())
            ->execute();
    }
}