<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing || $mailing->sent()) throw new HttpError(404);
include __DIR__ . '/_action.include.php';

printf('<h1>Delete: %s</h1>', $mailing->name());
Breadcrumb::setTopName($mailing->name());

echo (new CallbackLink(function () use ($mailing) {
    DB::query()
        ->delete('bulk_mail', $mailing->id())
        ->execute();
    throw new RedirectException(new URL('./'));
}))
    ->addClass('button button--danger')
    ->addChild('Yes, really delete this draft');