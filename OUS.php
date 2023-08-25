<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\ExceptionLog;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\Session\Authentication;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\UserMenu;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Group;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class OUS extends AbstractPlugin
{
    public static function userFromNetId(string $netId, bool $create = false): ?User
    {
        static $cache = [];
        $netId = strtolower(trim($netId));
        if (!isset($cache[$netId])) {
            $existing = DB::query()->from('user_source')
                ->where('provider_id', $netId)
                ->where('source = "cas" AND provider = "netid"')
                ->fetch();
            if ($existing) {
                // existing user found, return them
                $cache[$netId] = Users::get($existing['user_uuid']);
            } elseif ($create) {
                // no existing user found, but we've been tasked with creating them
                $user = new User();
                $user->addEmail(
                    $netId . '@unm.edu',
                    'Added from NetID',
                    true
                );
                $user->name($netId);
                // try to set name
                $name = PersonInfo::getFullNameFor($netId)
                    ?? PersonInfo::getFirstNameFor($netId);
                if ($name) {
                    $user->name($name);
                    $user['name_explicitly_set'] = true;
                }
                // insert user
                DB::beginTransaction();
                $user->insert();
                // insert authentication method
                DB::query()->insertInto('user_source', [
                    'user_uuid' => $user->uuid(),
                    'provider_id' => $netId,
                    'source' => 'cas',
                    'provider' => 'netid',
                    'created' => time(),
                ])->execute();
                DB::commit();
                // cache and return
                $cache[$netId] = $user;
            } else {
                $cache[$netId] = null;
            }
        }
        return $cache[$netId];
    }

    /**
     * as maintenance pull fresh permissions from user source. Cache them for
     * up to 24 hours, in case they disappear from the source or it becomes
     * unreachable for some reason.
     *
     * @return void
     */
    public static function cronJob_maintenance(): void
    {
        UserData::data(true);
    }

    public static function cronJob_maintenance_heavy(): void
    {
        SharedDB::query()->deleteFrom('person_info')
            ->where('updated < ?', strtotime('2 years ago'))
            ->execute();
    }

    public static function onShortCode_semester(ShortcodeInterface $s): ?string
    {
        $semester = Semesters::current();
        if (0 < $i = intval($s->getParameter('next'))) {
            while (--$i) $semester = $semester->next();
        }
        if (0 < $i = intval($s->getParameter('previous'))) {
            while (--$i) $semester = $semester->previous();
        }
        return $semester->__toString();
    }

    public static function onStaticUrlPermissions_ous(URL $url): bool
    {
        return Permissions::inMetaGroup('ous__edit');
    }

    public static function onStaticUrlName_ous(URL $url): string|null
    {
        if ($url->action() == 'index') return "OUS";
        else return null;
    }

    public static function onUserMenu_user(UserMenu $menu): void
    {
        if (Permissions::inMetaGroup('ous__edit')) $menu->addURL(new URL('/~ous/'));
    }

    /** @return string[] */
    public static function userNetIDs(string $userID = null): array
    {
        $userID = $userID ?? Session::uuid();
        $netIDs = array_map(
            function ($row) {
                return $row['provider_id'];
            },
            // @phpstan-ignore-next-line
            DB::query()->from('user_source')
                ->where('user_uuid = ?', [$userID])
                ->where('source = "cas" AND provider = "netid"')
                ->fetchAll()
        );
        $netIDs = array_filter($netIDs, function ($e): bool {
            if (!preg_match('/^[a-z].{1,19}$/', $e)) {
                return false;
            }
            if (preg_match('/[^a-z0-9_]/', $e)) {
                return false;
            }
            return true;
        });
        return array_values($netIDs);
    }

    /**
     * @param string $userID
     * @param Group[] $groups
     * @return void
     */
    public static function onUserGroups(string $userID, array &$groups): void
    {
        foreach (UserData::userGroups($userID) as $group) {
            if ($group instanceof Group || $group = Users::group($group)) {
                $groups[] = $group;
            }
        }
    }

    /**
     * Assign new users from CAS NetIDs a default name of their NetID.
     *
     * @param User $user
     * @param string $source
     * @param string $provider
     * @param string $netID
     * @return void
     */
    public static function onCreateUser_cas_netid(User $user, string $source, string $provider, string $netID): void
    {
        if (Config::get('unm.block_unknown_netids')) {
            if (!UserData::known($netID)) {
                ExceptionLog::log(new AccessDeniedError($netID . ' is not allowed to use this site'));
                throw new AccessDeniedError('You are not on the list of known NetIDs for this site');
            }
        }
        $user->name(
            PersonInfo::getFullNameFor($netID)
            ?? PersonInfo::getFirstNameFor($netID)
            ?? $netID
        );
        $user->addEmail($netID . '@unm.edu', 'Main campus NetID', true);
    }

    public static function onAuthentication(Authentication $auth): void
    {
        $user = $auth->user();
        if ($user['name_explicitly_set']) return;
        $netIDs = static::userNetIDs($user->uuid());
        foreach ($netIDs as $netID) {
            $name = PersonInfo::getFullNameFor($netID)
                ?? PersonInfo::getFirstNameFor($netID)
                ?? $netID;
            if ($name) {
                $user->name($name);
                $user['name_explicitly_set'] = true;
                $user->update();
            }
        }
    }
}