<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Content\Pages;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\ExceptionLog;
use DigraphCMS\HTML\A;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\Session\Authentication;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\UI\Toolbars\ToolbarSeparator;
use DigraphCMS\UI\Toolbars\ToolbarSpacer;
use DigraphCMS\UI\UserMenu;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Group;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Mailing;
use DigraphCMS_Plugins\unmous\ous_digraph_module\People\FacultyInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedBookmarks\SharedBookmarks;

// register additional event subscribers for this plugin
Dispatcher::addSubscriber(BulkMail::class);

class OUS extends AbstractPlugin
{

    const FACULTY_TITLE_ABBREVIATIONS = [
        'Assistant Vice President' => 'AVP',
        'Assistant VP of Research' => 'AVPR',
        'Associate Vice President' => 'AVP',
        'Vice President' => 'VP',
        'Executive Vice President' => 'EVP',
        'Provost & Executive Vice President for Academic Affairs' => 'Provost',
        'Senior Vice Provost' => 'SVP',
        'Vice President for Equity and Inclusion' => 'VPEI',
    ];

    /**
     * These titles will be passed through and used in personalized greetings,
     * all others that aren't abbreviated above will be replaced by "Professor"
     * for faculty, and no title will be used for staff
     */
    const FACULTY_GREETABLE_TITLES = [
        'President',
        'Dean',
        'Provost',
        'Chancellor',
    ];

    /**
     * @param array<string,array<string,ToolbarLink|ToolbarSeparator|ToolbarSpacer>> $buttons
     */
    public function onRichMediaToolbar(array &$buttons): void
    {
        // override the built-in page link button with one that uses shared bookmarks
        $buttons['insert']['link'] = (new ToolbarLink(
            'Link to a bookmark',
            'pages',
            null,
            new URL('&action=shared_bookmark')
        ))->setShortcut('Ctrl+K');
    }

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

    public static function cronJob_maintenance(): void
    {
        // pull fresh permissions from the user source.
        // cache them for up to 24 hours, in case they disappear from the source
        // or it becomes unreachable for some reason.
        UserData::data(true);
        // generate shared bookmarks for all of this site's pages
        if (Config::get('unm.shared_bookmarks.update')) static::updateSharedBookmarks();
    }

    protected static function updateSharedBookmarks(): void
    {
        new DeferredJob(
            function (DeferredJob $job) {
                $uuids = Pages::select()
                    ->order('updated asc')
                    ->query()
                    ->select('uuid', true);
                foreach ($uuids as $uuid) {
                    $uuid = $uuid['uuid'];
                    $job->spawn(function () use ($uuid) {
                        $page = Pages::get($uuid);
                        if (!$page) return "Page $uuid not found";
                        $url = $page->url();
                        if (!Permissions::url($url, Users::guest())) return "Page $uuid not publicly visible";
                        SharedBookmarks::set(
                            'link',
                            $page->uuid(),
                            $page->name(),
                            $url,
                            !!Config::get('unm.shared_bookmarks.searchable'),
                        );
                        return "Updated shared bookmark for $uuid";
                    });
                }
                return "Spawned shared bookmark link update jobs";
            },
            'update_shared_bookmarks'
        );
    }

    public static function cronJob_maintenance_heavy(): void
    {
        // delete old person_info records
        SharedDB::query()->deleteFrom('person_info')
            ->where('updated < ?', strtotime('2 years ago'))
            ->execute();
    }

    public static function onShortCode(ShortcodeInterface $s): ?string
    {
        // handle shared bookmark shortcodes
        $category = $s->getName();
        if (!SharedBookmarks::isCategory($category)) return null;
        $name = trim($s->getBbCode() ?? '');
        $bookmark = SharedBookmarks::get($category, $name);
        if (!$bookmark) return null;
        $title = trim($s->getContent() ?? '') ?: $bookmark->title();
        $link_title = $bookmark->title();
        // add fragment to URL
        $fragment = trim($s->getParameter('fragment', ''));
        if ($fragment) $fragment = '#' . $fragment;
        // begin building tag
        $a = new A($bookmark->url() . $fragment);
        $a->addChild($title);
        $a->setAttribute('title', $link_title);
        // add classes
        $a->addClass('shared-bookmark');
        $a->addClass('shared-bookmark--' . $category);
        if ($s->getParameter('class')) {
            foreach (explode(' ', $s->getParameter('class')) as $class) {
                $class = trim($class);
                if (!$class) continue;
                $a->addClass($class);
            }
        }
        // return finished link
        return $a;
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

    public static function onShortCode_email_dear_line(ShortcodeInterface $s): ?string
    {
        $netIds = [];
        $emails = [BulkMail::toEmail()];
        if ($user = BulkMail::toUser()) {
            $netIds = OUS::userNetIDs($user);
            foreach ($user->emails() as $email) {
                if (str_ends_with($email, '@unm.edu')) {
                    $netIds[] = preg_replace('/@unm\.edu$/', '', $email);
                    continue;
                }
                $emails[] = $email;
            }
        }
        if (str_ends_with($email = BulkMail::toEmail(), '@unm.edu')) {
            $netIds[] = preg_replace('/@unm\.edu$/', '', $email);
        }
        $netIds = array_unique($netIds);
        $emails = array_unique($emails);
        foreach ($netIds as $netId) {
            if ($faculty = FacultyInfo::search($netId)) {
                $title = $faculty->title;
                $title = preg_replace('/^(Interim|Acting) /i', '', $title);
                if (isset(static::FACULTY_TITLE_ABBREVIATIONS[$title])) $title = static::FACULTY_TITLE_ABBREVIATIONS[$title];
                elseif (!in_array($title, static::FACULTY_GREETABLE_TITLES)) $title = 'Professor';
                return sprintf("Dear %s %s,", $title, $faculty->lastName);
            } elseif ($name = PersonInfo::getFullNameFor($netId)) {
                return sprintf("Dear %s,", $name);
            }
        }
        foreach ($emails as $email) {
            if ($name = PersonInfo::getFullNameFor($email)) {
                return sprintf("Dear %s,", $name);
            }
        }
        if ($user = BulkMail::toUser()) {
            return sprintf('Dear %s,', $user->name());
        }
        return 'Dear Colleague,';
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
