<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail;

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Email\Emails;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\UI\UserMenu;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients\AbstractRecipientSource;
use ReflectionClass;

class BulkMail
{
    const EDITOR_ACTIONS = ['_create'];
    const EDITOR_ACTION_PREFIXES = ['edit', 'recipients', 'copy'];
    const ADMIN_ACTIONS = [];
    const ADMIN_ACTION_PREFIXES = ['send'];

    public static function mailings(): MailingSelect
    {
        return (new MailingSelect)
            ->where('sent is not null')
            ->order('sent DESC');
    }

    public static function drafts(): MailingSelect
    {
        return (new MailingSelect)
            ->where('sent is null')
            ->order('updated DESC');
    }

    public static function mailing(int $id, bool $bust_cache = false): ?Mailing
    {
        static $cache = [];
        if ($bust_cache || !isset($cache[$id])) {
            $cache[$id] = (new MailingSelect)->where('id', $id)->fetch();
        }
        return $cache[$id];
    }

    public static function message(int $id, bool $bust_cache = false): ?Message
    {
        static $cache = [];
        if ($bust_cache || !isset($cache[$id])) {
            $cache[$id] = (new MessageSelect)->where('id', $id)->fetch();
        }
        return $cache[$id];
    }

    /**
     * @return array<string,AbstractRecipientSource>
     */
    public static function sources(): array
    {
        $sources = [];
        Dispatcher::dispatchEvent('onBulkMailRecipientSources', [&$sources]);
        $sources = array_filter($sources);
        ksort($sources);
        return $sources;
    }

    public static function toUser(): ?User
    {
        $user = Context::fields()['bulk_mail.user'];
        if ($user instanceof User) return $user;
        else return null;
    }

    public static function toEmail(): ?string
    {
        $email = Context::fields()['bulk_mail.email'];
        if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) return $email;
        else return null;
    }

    /**
     * @param array<string,AbstractRecipientSource> $sources
     * @return void
     */
    public static function onBulkMailRecipientSources(array &$sources): void
    {
        /** @var string $name */
        foreach (array_keys(Config::get('bulk_mail.sources')) as $name) {
            if ($source = static::source($name)) {
                $sources[$name] = $source;
            }
        }
    }

    public static function source(string $name): ?AbstractRecipientSource
    {
        return Dispatcher::firstValue('onBulkMailRecipientSource', [$name]);
    }

    public static function onBulkMailRecipientSource(string $name): ?AbstractRecipientSource
    {
        // return configured recipient source
        if ($config = Config::get('bulk_mail.sources.' . $name)) {
            // require source to be enabled
            if (!$config['enabled']) return null;
            // require metagroups if specified
            if (@$config['require-groups']) {
                if (!Permissions::inMetaGroups($config['require-groups'])) return null;
            }
            // return instance of source
            $reflection = new ReflectionClass($config['class']);
            $args = @$config['args'] ?? [];
            array_unshift($args, $name);
            // @phpstan-ignore-next-line this is actually okay
            return $reflection->newInstanceArgs($args);
        }
        // return null if not found
        return null;
    }

    /**
     * Return a list of all categories for which the current user is authorized
     * to edit/send. Key is the underlying email category name, value is the
     * user-friendly label.
     *
     * @return array<string,string>
     */
    public static function categories(): array
    {
        $categories = [];
        /** @var string $category */
        foreach (Config::get('bulk_mail.categories') as $category => $config) {
            // require category to be enabled for bulk mailing
            if (!$config['enabled']) continue;
            // require metagroups if specified
            if (@$config['require-groups']) {
                if (!Permissions::inMetaGroups($config['require-groups'])) continue;
            }
            // add category/label to output array
            $categories[$category] = Emails::categoryLabel($category);
        }
        return $categories;
    }

    public static function onUserMenu_user(UserMenu $menu): void
    {
        $menu->addURL((new URL('/bulk_mail/'))->setName('Bulk mail'));
    }

    public static function onStaticUrlPermissions_bulk_mail(URL $url, User $user): bool
    {
        if (in_array($url->action(), static::ADMIN_ACTIONS) || in_array($url->action(), static::ADMIN_ACTIONS)) {
            return Permissions::inMetaGroups(['bulk_mail__admin'], $user);
        } elseif (in_array($url->action(), static::EDITOR_ACTIONS) || in_array($url->action(), static::EDITOR_ACTIONS)) {
            return Permissions::inMetaGroups(['bulk_mail__edit'], $user);
        } else {
            return Permissions::inMetaGroups(['bulk_mail__view'], $user);
        }
    }
}