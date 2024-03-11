<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\SharedBookmarks;

use DigraphCMS\Cache\Cache;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use Envms\FluentPDO\Queries\Select;

class SharedBookmarks
{
    public static function select(): Select
    {
        return SharedDB::query()
            ->from('shared_bookmark')
            ->asObject(SharedBookmark::class); //@phpstan-ignore-line this is right
    }

    public static function get(string $category, string $name): ?SharedBookmark
    {
        return self::select()
            ->where('category', strtolower($category))
            ->where('name', strtolower($name))
            ->fetch() ?: null;
    }

    public static function getById(int $id): ?SharedBookmark
    {
        return self::select()
            ->where('id', $id)
            ->fetch() ?: null;
    }

    public static function isCategory(string $category): bool
    {
        return Cache::get(
            'ous/shared_bookmark_categories/' . md5($category),
            function () use ($category) {
                return self::select()
                    ->where('category', $category)
                    ->count() > 0;
            },
            600
        );
    }

    public static function set(string $category, string $name, string $title, string $url): SharedBookmark
    {
        $category = strtolower(trim($category));
        $name = strtolower(trim($name));
        $title = substr(trim($title), 0, 255);
        $url = trim($url);
        $existing = self::get($category, $name);
        if ($existing) {
            SharedDB::query()
                ->update('shared_bookmark')
                ->set([
                    'title' => $title,
                    'url' => $url,
                ])
                ->where('id', $existing->id())
                ->execute();
        } else {
            SharedDB::query()
                ->insertInto('shared_bookmark')
                ->values([
                    'category' => $category,
                    'name' => $name,
                    'title' => $title,
                    'url' => $url,
                ])
                ->execute();
        }
        return self::get($category, $name);
    }
}
