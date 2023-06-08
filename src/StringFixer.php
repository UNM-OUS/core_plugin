<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

class StringFixer
{
    public static function jobTitle(?string $name): ?string
    {
        return static::runFixers($name, ['job']);
    }

    public static function organization(?string $name): ?string
    {
        return static::runFixers($name, ['college']);
    }

    public static function college(?string $name): ?string
    {
        return static::runFixers($name, ['college']);
    }

    public static function department(?string $name): ?string
    {
        return static::runFixers($name, ['department']);
    }

    public static function program(?string $name): ?string
    {
        return static::runFixers($name, ['program']);
    }

    public static function major(?string $name): ?string
    {
        return static::runFixers($name, ['major']);
    }

    /**
     * @param string|null $input
     * @param string[] $categories
     */
    public static function runFixers(?string $input, array $categories): ?string
    {
        $input = trim($input ?? '');
        $input = preg_replace('/ +/', ' ', $input);
        if (!$input) return null;
        foreach ($categories as $c) {
            if ($output = static::run($input, $c)) return $output;
        }
        return $input;
    }

    protected static function run(string $input, string $category): string
    {
        static $cache = [];
        $id = md5(serialize([$input, $category]));
        if (!isset($cache[$id])) {
            // look for a match
            $result = SharedDB::query()->from('stringfix')
                ->where('input', $input)
                ->where('category', $category)
                ->limit(1)
                ->fetch();
            // add result to cache if it exists
            if ($result) $cache[$id] = $result['output'];
            else {
                // otherwise save a null in the cache
                $cache[$id] = null;
                // if there is no match, add this to stringfix with the 'needs_review' flag
                SharedDB::query()->insertInto(
                    'stringfix',
                    [
                        'input' => $input,
                        'output' => $input,
                        'category' => $category,
                        'needs_review' => true
                    ]
                )->execute();
            }
        }
        return $cache[$id] ?? $input;
    }
}