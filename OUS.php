<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\Session\Authentication;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\UserMenu;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class OUS extends AbstractPlugin
{

    public static function onStaticUrlPermissions_ous(URL $url)
    {
        return Permissions::inMetaGroup('ous__edit');
    }

    public static function onStaticUrlName_ous(URL $url)
    {
        if ($url->action() == 'index') return "OUS";
        else return null;
    }

    public static function onUserMenu_user(UserMenu $menu)
    {
        if (Permissions::inMetaGroup('ous__edit')) $menu->addURL(new URL('/~ous/'));
    }

    public static function userNetIDs(string $userID = null): array
    {
        $userID = $userID ?? Session::uuid();
        return array_map(
            function ($row) {
                return $row['provider_id'];
            },
            DB::query()->from('user_source')
                ->where('user_uuid = ?', [$userID])
                ->where('source = "cas" AND provider = "netid"')
                ->fetchAll()
        );
    }

    public static function onUserGroups(string $userID, array &$groups)
    {
        foreach (UserData::userGroups($userID) as $group) {
            if ($group = Users::group($group)) {
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
    public static function onCreateUser_cas_netid(User $user, string $source, string $provider, string $netID)
    {
        if (Config::get('unm.block_unknown_netids')) {
            if (!UserData::known($netID)) {
                throw new AccessDeniedError('You are not on the list of known NetIDs for this site');
            }
        }
        $user->name(
            UserData::netIDName($netID)
                ?? PersonInfo::getFullNameFor($netID)
                ?? PersonInfo::getFirstNameFor($netID)
                ?? $netID
        );
        $user->addEmail($netID . '@unm.edu', 'Main campus NetID', true);
    }

    public static function onAuthentication(Authentication $auth)
    {
        $user = $auth->user();
        if ($user['name_explicitly_set']) return;
        $netIDs = static::userNetIDs($user->uuid());
        foreach ($netIDs as $netID) {
            $name = UserData::netIDName($netID)
                ?? PersonInfo::getFullNameFor($netID);
            if ($name) {
                $user->name($name);
                $user['name_explicitly_set'] = true;
                $user->update();
            }
        }
    }
}
