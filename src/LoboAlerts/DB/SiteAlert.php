<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB;

use DateTime;
use DigraphCMS\Digraph;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\LoboAlert;

class SiteAlert extends LoboAlert
{
    const HELPER_CLASS = SiteAlerts::class;

    /** @var int|null */
    protected $start_time;
    /** @var int|null */
    protected $end_time;

    public function __construct(
        string $title = null,
        string $content = null,
        string $class = 'warning',
        string $uuid = null,
        int|string|DateTime|null $start = null,
        int|string|DateTime|null $end = null
    ) {
        if ($title) $this->title = $title;
        if ($content) $this->content = $content;
        if ($class) $this->class = $class;
        if ($uuid) $this->uuid = $uuid;
        if ($start) $this->setStart($start);
        if ($end) $this->setEnd($end);
    }

    public function editUrl(): URL
    {
        return new URL(
            '/ous/alert_banners/edit_site_alert:' . $this->uuid()
        );
    }

    public function setStart(int|string|DateTime|null $start): static
    {
        if (!$start) $this->start_time = null;
        else $this->start_time = Format::parseDate($start)->getTimestamp();
        return $this;
    }

    public function setEnd(int|string|DateTime|null $end): static
    {
        if (!$end) $this->end_time = null;
        else $this->end_time = Format::parseDate($end)->getTimestamp();
        return $this;
    }

    public function start(): ?DateTime
    {
        if ($this->start_time) return Format::parseDate($this->start_time);
        else return null;
    }

    public function end(): ?DateTime
    {
        if ($this->end_time) return Format::parseDate($this->end_time);
        else return null;
    }

    public function content(): string
    {
        return (new RichContent($this->content))->__toString();
    }

    public function contentSource(): string
    {
        return $this->content;
    }

    public function update(): void
    {
        static::HELPER_CLASS::update($this);
    }

    public function create(): static
    {
        static::HELPER_CLASS::create($this);
        return $this;
    }

    public function delete(): void
    {
        static::HELPER_CLASS::delete($this);
    }
}