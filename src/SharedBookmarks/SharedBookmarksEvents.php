<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\SharedBookmarks;

use DigraphCMS\HTML\A;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class SharedBookmarksEvents
{
    public static function onShortCode(ShortcodeInterface $s): ?string
    {
        $category = $s->getName();
        if (!SharedBookmarks::isCategory($category)) return null;
        $name = trim($s->getBbCode() ?? '');
        $bookmark = SharedBookmarks::get($category, $name);
        if (!$bookmark) return null;
        $title = trim($s->getContent() ?? '') ?: $bookmark->title();
        $link_title = $bookmark->title();
        $a = new A($bookmark->url());
        $a->addChild($title);
        $a->setAttribute('title', $link_title);
        $a->addClass('shared-bookmark');
        $a->addClass('shared-bookmark--' . $category);
        return $a;
    }

    public static function cronJob_maintenance_heavy(): void
    {
        // TODO regenerate bookmarks for RPM and UAP
    }
}
