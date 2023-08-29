<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class AllUsers extends AbstractRecipientSource
{
    function label(): string
    {
        return 'All registered users';
    }

    function recipients(): iterable
    {
        $users = Users::select();
        while ($user = $users->fetch()) {
            /** @var User $user */
            if ($email = $user->primaryEmail()) {
                yield new Recipient($email, $user->uuid());
            }
        }
    }

    function count(): int
    {
        return Users::select()->count();
    }
}
