<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts;

class AlertBanner
{
    /** @var string */
    protected $title;
    /** @var string */
    protected $content;
    /** @var string|null */
    protected $uuid;
    /** @var string */
    protected $class;

    public static function parse(string $html, string $class = 'warning', string $uuid = null): ?AlertBanner
    {
        $html = trim($html);
        $parsed = preg_match('/^<h[1-6].+?>(.+?)<\/h[1-6]>(.+)$/is', $html, $matches);
        if ($parsed === false) return null;
        $title = trim(strip_tags(html_entity_decode(@$matches[1] ? $matches[1] : '')), '\t\n\r\0\x0B ');
        $content = trim(@$matches[2] ? $matches[2] : '');
        return new AlertBanner(
            $title,
            $content,
            $class,
            $uuid
        );
    }

    public function __construct(string $title, string $content, string $class = 'warning', string $uuid = null)
    {
        $this->title = $title;
        $this->content = $content;
        $this->uuid = $uuid;
        $this->class = $class;
    }

    public function render(): string
    {
        return sprintf(
            implode(PHP_EOL, [
                '<details class="loboalert loboalert--%s" id="%s">',
                '<summary class="loboalert__title">%s</summary>',
                '<div class="loboalert__content">%s</div>',
                '</details>',
            ]),
            $this->class(),
            $this->id(),
            $this->title(),
            $this->content(),
        );
    }

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function uuid(): string
    {
        return md5(
            $this->uuid
            ?? serialize([$this->title, $this->content, $this->class])
        );
    }

    public function id(): string
    {
        return 'alert-' . $this->uuid();
    }

    public function class (): string
    {
        return $this->class;
    }
}