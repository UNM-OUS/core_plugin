<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\Config;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\UI\Theme;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class Plugin extends \DigraphCMS\Plugins\AbstractPlugin
{
    public function registered()
    {
        // hide signin link if block_unknown_netids config is true
        if (Config::get('unm.block_unknown_netids')) {
            Theme::addBodyClass('block-unknown-netids');
        }
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
    }

    public function mediaFolders(): array
    {
        return [__DIR__ . '/../media'];
    }

    public function routeFolders(): array
    {
        return [__DIR__ . '/../routes'];
    }

    public function templateFolders(): array
    {
        return [__DIR__ . '/../templates'];
    }

    public function phinxFolders(): array
    {
        return [];
    }
}
