<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Curl\CurlHelper;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\UI\Templates;

class LoboAlert
{
    /** @var string */
    protected $title;
    /** @var string */
    protected $content;
    /** @var string|null */
    protected $identifier;
    /** @var string */
    protected $class;

    public static function parse(string $html, string $class = 'warning', string $identifier = null): ?LoboAlert
    {
        $html = trim($html);
        $parsed = preg_match('/^<h[1-6].+?>(.+?)<\/h[1-6]>(.+)$/is', $html, $matches);
        $title = trim(strip_tags(html_entity_decode($matches[1])), '\t\n\r\0\x0B ');
        $content = trim($matches[2]);
        return new LoboAlert(
            $title,
            $content,
            $class,
            $identifier
        );
    }

    public function __construct(string $title, string $content, string $class = 'warning', string $identifier = null)
    {
        $this->title = $title;
        $this->content = $content;
        $this->identifier = $identifier;
        $this->class = $class;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function identifier(): string
    {
        return  md5(
            $this->identifier
                ?? serialize([$this->title, $this->content, $this->class])
        );
    }

    public function id(): string
    {
        return 'loboalert-' . $this->identifier();
    }

    public function class(): string
    {
        return $this->class;
    }

    public function render(): string
    {
        return Templates::render('unm/loboalert.php', ['loboalert' => $this]);
    }
}
