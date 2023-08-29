<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing) throw new HttpError(404);
include __DIR__ . '/_actions.include.php';

DB::query()->insertInto(
    'bulk_mail',
    [
        'name' => $mailing->name(),
        '`from`' => $mailing->from(),
        'subject' => $mailing->subject(),
        'body' => $mailing->body(),
        'sources' => implode(',', $mailing->sourceNames()),
        'extra_recipients' => $mailing->extraRecipients(),
        'category' => $mailing->category(),
        'created' => time(),
        'created_by' => Session::uuid(),
        'updated' => time(),
        'updated_by' => Session::uuid(),
    ]
)->execute();

Notifications::flashConfirmation('Copied ' . $mailing->name());
throw new RedirectException(new URL('./'));
