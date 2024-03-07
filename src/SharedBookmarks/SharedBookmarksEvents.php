<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\SharedBookmarks;

use DigraphCMS\Content\Pages;
use DigraphCMS\Cron\DeferredJob;
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
        // generate bookmarks for all of this site's pages
        $uuids = Pages::select()
            ->order('updated asc')
            ->query()
            ->select('uuid', true);
        foreach ($uuids as $uuid) {
            $uuid = $uuid['uuid'];
            new DeferredJob(
                function () use ($uuid) {
                    $page = Pages::get($uuid);
                    if (!$page) return "Page $uuid not found";
                    SharedBookmarks::set(
                        'link',
                        $page->uuid(),
                        $page->name(),
                        $page->url(),
                    );
                    return "Updated shared bookmark for $uuid";
                },
                'update_shared_bookmark'
            );
        }
    }
}
