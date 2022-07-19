<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\Config;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class OUS extends AbstractPlugin
{

    public static function userNetIDs(string $userID): array
    {
        return array_map(
            function ($row) {
                return $row['provider_id'];
            },
            DB::query()->from('user_source')
                ->where('user_uuid = ?', [Context::url()->action()])
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
        $user->name(UserData::netIDName($netID) ?? $netID);
        $user->addEmail($netID . '@unm.edu', 'Main campus NetID', true);
    }
}
