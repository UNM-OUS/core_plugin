<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Curl\CurlHelper;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Group;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Exception;
use Spyc;

class UserData
{
    /** @return array<int,Group|string> */
    public static function userGroups(string $userID): array
    {
        $groups = [];
        // pull faculty group
        foreach (static::userNetIDs($userID) as $netID) {
            if (static::netIdIsFaculty($netID)) {
                $groups[] = static::facultyGroup();
                break;
            }
        }
        // pull faculty group
        foreach (static::userNetIDs($userID) as $netID) {
            if (static::netIdIsVotingFaculty($netID)) {
                $groups[] = static::votingFacultyGroup();
                break;
            }
        }
        // pull staff group
        foreach (static::userNetIDs($userID) as $netID) {
            if (static::netIdIsStaff($netID)) {
                $groups[] = static::staffGroup();
                break;
            }
        }
        // pull configured groups
        foreach (static::userNetIDs($userID) as $netID) {
            foreach (static::netIDGroups($netID) as $group) {
                $groups[] = $group;
            }
        }
        return array_unique($groups);
    }

    public static function netIdIsFaculty(string $netID): ?bool
    {
        static $cache = [];
        if (!$netID) return null;
        $netID = strtolower($netID);
        return @$cache[$netID]
            ?? ($cache[$netID] = !!SharedDB::query()->from('all_faculty')
                ->where('netid', $netID)->count());
    }

    public static function netIdIsVotingFaculty(string $netID): ?bool
    {
        static $cache = [];
        if (!$netID) return null;
        $netID = strtolower($netID);
        return @$cache[$netID]
            ?? ($cache[$netID] = !!SharedDB::query()->from('voting_faculty')
                ->where('netid', $netID)->count());
    }

    public static function netIdIsStaff(string $netID): ?bool
    {
        static $cache = [];
        if (!$netID) return null;
        $netID = strtolower($netID);
        return @$cache[$netID]
            ?? ($cache[$netID] = !!SharedDB::query()->from('staff')
                ->where('netid', $netID)->count());
    }

    public static function facultyGroup(): Group
    {
        static $group;
        return $group ?? $group = new Group('faculty', 'UNM Faculty', new URL('/~ous/person_databases/all_faculty/'));
    }

    public static function votingFacultyGroup(): Group
    {
        static $group;
        return $group ?? $group = new Group('voting_faculty', 'UNM Voting Faculty', new URL('/~ous/person_databases/voting_faculty/'));
    }

    public static function staffGroup(): Group
    {
        static $group;
        return $group ?? $group = new Group('staff', 'UNM Staff', new URL('/~ous/person_databases/all_staff/'));
    }

    /** @return string[] */
    public static function netIDs(string $userID): array
    {
        static $cache = [];
        return @$cache[$userID] ?? $cache[$userID] = static::userNetIDs($userID);
    }

    public static function userFromNetID(string $netID): ?User
    {
        $query = DB::query()->from('user_source')
            ->where('provider_id = ?', [$netID])
            ->where('source = "cas" AND provider = "netid"');
        if ($result = $query->fetch()) return Users::get($result['user_uuid']);
        else return null;
    }

    /** @return string[] */
    protected static function userNetIDs(string $userID): array
    {
        $query = DB::query()->from('user_source')
            ->where('user_uuid = ?', [$userID])
            ->where('source = "cas" AND provider = "netid"');
        return array_map(
            function ($row) {
                return $row['provider_id'];
            },
            // @phpstan-ignore-next-line
            $query->fetchAll()
        );
    }

    /**
     * @return string[]
     */
    public static function netIDGroups(string $netID): array
    {
        // @phpstan-ignore-next-line
        return @static::data()[$netID]['groups'] ?? [];
    }

    public static function known(string $netID): bool
    {
        return @static::data()[$netID] !== null;
    }

    /**
     * @return array<string,array<string,string[]>>
     */
    public static function data(bool $forceRefresh = false): array
    {
        // return merged/filtered data from two sources
        return array_filter(
            array_merge(
                // get data from central config file
                static::getData($forceRefresh),
                // get data from local config
                Config::get('unm.known_netids')
            )
        );
    }

    /**
     * @return array<string,array<string,string[]>>
     */
    public static function getData(bool $forceRefresh = false): array
    {
        // use a static cache variable
        static $cache;
        if ($cache !== null && !$forceRefresh) return $cache;
        // if force refresh is true or stored cache isn't set or is expired, run job to get data
        if ($forceRefresh || !Cache::exists('unm/userdata') || Cache::expired('unm/userdata')) {
            $data = CurlHelper::get(Config::get('unm.user_source'));
            if ($data === null) throw new Exception('UNM user source failed to load: ' . CurlHelper::error());
            if ($data = Spyc::YAMLLoadString($data)) Cache::set('unm/userdata', $data, -1);
            else throw new Exception('UNM user source failed to parse');
            return $cache = $data;
        }
        // otherwise just return the main cache value
        return $cache = Cache::get('unm/userdata');
    }
}