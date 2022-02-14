<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Cache\CacheNamespace;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use Spyc;

class UserData
{
    public static function userGroups(string $userID): array
    {
        $groups = [];
        foreach (static::userNetIDs($userID) as $netID) {
            foreach (static::netIDGroups($netID) as $group) {
                $groups[] = $group;
            }
        }
        return array_unique($groups);
    }

    public static function netIDs(string $userID): array
    {
        static $cache = [];
        return @$cache[$userID] ?? $cache[$userID] = static::userNetIDs($userID);
    }

    protected static function userNetIDs(string $userID): array
    {
        $query = DB::query()->from('user_source')
            ->where('user_uuid = ?', [$userID])
            ->where('source = "cas" AND provider = "netid"');
        return array_map(
            function ($row) {
                return $row['provider_id'];
            },
            $query->execute()->fetchAll()
        );
    }

    public static function netIDGroups(string $netID): array
    {
        return @static::data()[$netID] ?? [];
    }

    public static function known(string $netID): bool
    {
        return @static::data()[$netID] !== null;
    }

    public static function data(): array
    {
        static $data;
        return $data ?? $data = static::buildData();
    }

    protected static function buildData(): array
    {
        // return merged/filtered data from two sources
        return array_filter(array_merge(
            // get data from central config file
            Cache::get(
                'unm/userdata',
                function () {
                    return Spyc::YAMLLoadString(
                        file_get_contents('https://secretary.unm.edu/known_netids.yaml')
                    );
                },
                Config::get('unm.userdata_ttl')
            ),
            // get data from local config
            Config::get('unm.known_netids')
        ));
    }

    public static function cache(): CacheNamespace
    {
        static $cache;
        return $cache ?? $cache = new CacheNamespace('unm/userdata');
    }
}
