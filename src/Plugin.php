<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\Users\User;

class Plugin extends \DigraphCMS\Plugins\AbstractPlugin
{
    /**
     * Assign new users from CAS NetIDs a default name of their NetID.
     *
     * @param User $user
     * @param string $source
     * @param string $provider
     * @param string $providerID
     * @return void
     */
    public static function onCreateUser_cas_netid(User $user, string $source, string $provider, string $providerID)
    {
        $user->name($providerID);
    }

    public function isEventSubscriber(): bool
    {
        return false;
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
