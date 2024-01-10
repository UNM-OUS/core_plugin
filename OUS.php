<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\ExceptionLog;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\Session\Authentication;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\UserMenu;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Group;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Mailing;

// register additional event subscribers for this plugin
Dispatcher::addSubscriber(BulkMail::class);
Dispatcher::addSubscriber(ShortCodeLinks::class);

class OUS extends AbstractPlugin
{

    public static function cronJob_frequent(): void
    {
        // get mailings that are scheduled for now or earlier and not sent
        $mailings = BulkMail::scheduled()->where('scheduled <= ?', time());
        /** @var Mailing $mailing */
        foreach ($mailings as $mailing) {
            $mailing->send();
        }
    }

    public static function transferTime(DateTime|int|string $original_time, DateTime $original_reference, DateTime $new_reference): DateTime
    {
        $original_time = Format::parseDate($original_time);
        // normalize time of references
        $original_reference = (clone $original_reference)->setTime(0, 0, 0);
        $new_reference = (clone $new_reference)->setTime(0, 0, 0);
        // create a new time that is the same amount of time from $new_reference
        $interval = $original_reference->diff($original_time);
        $new_time = $new_reference->add($interval);
        // manually set the time to be exactly the same, to correctly handle time changes (mostly)
        $new_time->setTime(
            intval($original_time->format('G')),
            intval($original_time->format('i')),
            intval($original_time->format('s')),
        );
        return $new_time;
    }

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
    public static function userNetIDs(string|User $userID = null): array
    {
        if ($userID instanceof User) $userID = $userID->uuid();
        $userID = $userID ?? Session::uuid();
        $netIDs = array_map(
            function ($row) {
                return $row['provider_id'];
            },
            // @phpstan-ignore-next-line
            DB::query()
                ->from('user_source')
                ->where('user_uuid ', $userID)
                ->where('source', 'cas')
                ->where('provider', 'netid')
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
