<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\SharedFragments;

use DigraphCMS\Cache\Cache;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use Envms\FluentPDO\Queries\Select;

class SharedFragments
{

    public static function select(): Select
    {
        return SharedDB::query()
            ->from('shared_fragment')
            ->asObject(SharedFragment::class); //@phpstan-ignore-line this is right
    }

    public static function get(string $category, string $name): ?SharedFragment
    {
        return self::select()
            ->where('category', strtolower($category))
            ->where('name', strtolower($name))
            ->fetch() ?: null;
    }

    public static function getById(int $id): ?SharedFragment
    {
        return self::select()
            ->where('id', $id)
            ->fetch() ?: null;
    }

    public static function categoryExists(string $category): bool
    {
        return Cache::get(
            'ous/shared_fragment_categories/' . md5($category),
            function () use ($category) {
                return self::select()
                    ->where('category', $category)
                    ->count() > 0;
            },
            600,
        );
    }

    public static function set(string $category, string $name, string $value, bool $searchable): SharedFragment
    {
        $category = strtolower(trim($category));
        $name = strtolower(trim($name));
        $existing = self::get($category, $name);
        $value = trim($value);
        if ($existing) {
            SharedDB::query()
                ->update('shared_fragment')
                ->set([
                    'value'      => $value,
                    'searchable' => $searchable ? '1' : '0'
                ])
                ->where('id', $existing->id())
                ->execute();
        }
        else {
            SharedDB::query()
                ->insertInto('shared_fragment')
                ->values([
                    'category'   => $category,
                    'name'       => $name,
                    'value'      => $value,
                    'searchable' => $searchable ? '1' : '0'
                ])
                ->execute();
        }
        return self::get($category, $name);
    }

    /**
     * Score how well a bookmark matches a given query.
     */
    public static function scoreSearchResult(SharedFragment $bookmark, string $query): int
    {
        $query = strtolower($query);
        $score = 0;
        if ($bookmark->name() == $query || $bookmark->value() == $query) {
            $score += 100;
        }
        $score += similar_text(metaphone($query), metaphone($bookmark->value()));
        $score += similar_text(metaphone($query), metaphone($bookmark->category() . ' ' . $bookmark->name()));
        return $score;
    }

}