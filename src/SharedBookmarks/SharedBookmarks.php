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

    public static function set(string $category, string $name, string $title, string $url, bool $searchable): SharedBookmark
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
                    'searchable' => $searchable ? '1' : '0'
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
                    'searchable' => $searchable ? '1' : '0'
                ])
                ->execute();
        }
        return self::get($category, $name);
    }

    /**
     * Score how well a bookmark matches a given query.
     */
    public static function scoreSearchResult(SharedBookmark $bookmark, string $query): int
    {
        $query = strtolower($query);
        $score = 0;
        if ($bookmark->name() == $query || $bookmark->title() == $query) {
            $score += 100;
        }
        $score += similar_text(metaphone($query), metaphone($bookmark->title()));
        $score += similar_text(metaphone($query), metaphone($bookmark->category() . ' ' . $bookmark->name()));
        return $score;
    }
}
