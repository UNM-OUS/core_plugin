<?php

use DigraphCMS\Cache\CacheableState;
use DigraphCMS\Cache\CachedInitializer;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;

require_once __DIR__ . '/vendor/autoload.php';

// run initial configuration
CachedInitializer::run(
    'initialization',
    function (CacheableState $state) {
        $state->config('paths.base', __DIR__);
        $state->config('paths.web', __DIR__);
    }
);

// set up module-development-specific phinx config, such as using in-memory sqlite
// this file would not work for a whole site, as it won't actually persist data
return
    [
        'paths' => [
            'migrations' => array_merge(
                [__DIR__ . '/phinx/migrations'],
                DB::migrationPaths()
            ),
            'seeds' => array_merge(
                [__DIR__ . '/phinx/seeds'],
                DB::seedPaths()
            ),
        ],
        'environments' => [
            'default_migration_table' => 'phinxlog',
            'default_environment' => 'current',
            'current' => [
                'name' => 'Current environment',
                'connection' => new PDO('sqlite::memory:')
            ]
        ],
        'version_order' => 'creation',
    ];