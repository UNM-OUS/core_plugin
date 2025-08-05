<?php

use DigraphCMS\Cache\CacheableState;
use DigraphCMS\Cache\CachedInitializer;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Plugins\Plugins;

require_once __DIR__ . '/../vendor/autoload.php';

// run initial configuration
CachedInitializer::config(
    function (CacheableState $state) {
        $state->mergeConfig(Config::parseYamlFile(__DIR__ . '/../../global.yaml'), true);
        $state->mergeConfig(Config::parseYamlFile(__DIR__ . '/../demo/config.yaml'), true);
        $state->mergeConfig(Config::parseYamlFile(__DIR__ . '/../demo/env.yaml'), true);
        $state->config('paths.base', __DIR__ . '/../demo');
        $state->config('paths.web', __DIR__ . '/../demo');
    }
);

// load composer plugins
Plugins::loadFromComposer(__DIR__ . '/../composer.lock');

// load main repo as a plugin
Plugins::load(realpath(__DIR__ . '/..'), false);
DB::addPhinxPath(realpath(__DIR__ . '/../phinx'));