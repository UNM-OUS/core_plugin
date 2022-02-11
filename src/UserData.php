<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\Cache\CacheNamespace;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;

class UserData
{
    public static function userGroups(string $userID): array {
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

    public static function data(): array
    {
        static $data;
        return $data ?? $data = static::buildData();
    }

    protected static function buildData(): array
    {
        $data = array_filter(Config::get('unm.known_netids'));
        // TODO: pull and merge centralized data from ... somewhere?
        return $data;
    }

    public static function cache(): CacheNamespace
    {
        static $cache;
        return $cache ?? $cache = new CacheNamespace('unm/userdata');
    }
}
