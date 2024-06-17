<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Permalinks;

use DigraphCMS\Content\Slugs;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\Session\Session;
use URLify;

class Permalinks
{
    public static function create(string $target, string $slug = null): Permalink
    {
        $slug = $slug ?? strtolower(Digraph::uuid());
        $slug = static::cleanSlug($slug);
        // insert into database
        DB::query()
            ->insertInto('permalink', [
                'target' => $target,
                'slug' => $slug,
                'count' => 0,
                'created' => time(),
                'created_by' => Session::uuid(),
                'updated' => time(),
                'updated_by' => Session::uuid()
            ])
            ->execute();
        // return object re-retrieved from database sort of as a sanity check
        return static::get($slug);
    }

    public static function cleanSlug(string $slug): string
    {
        // run through URLify
        $slug = URLify::transliterate($slug);
        // trim and clean up
        $slug = strtolower($slug);
        $slug = preg_replace('@[^' . Slugs::SLUG_CHARS . '\/]+@', '_', $slug);
        $slug = preg_replace('@/+@', '_', $slug);
        $slug = trim($slug, '_');
        // return
        return $slug;
    }

    public static function get(string $slug): ?Permalink
    {
        return static::select()
            ->where('slug', $slug)
            ->fetch();
    }

    public static function select(): PermalinkSelect
    {
        return new PermalinkSelect(
            DB::query()->from('permalink')
        );
    }
}
