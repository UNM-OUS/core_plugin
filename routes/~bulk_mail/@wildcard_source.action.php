<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing || $mailing->sent()) throw new HttpError(404);
include __DIR__ . '/_action.include.php';

printf('<h1>Source: %s</h1>', $mailing->name());
Breadcrumb::setTopName($mailing->name());

echo '<dl>';

printf('<dt>From address<dt><dd>%s</dd>', $mailing->from());

printf('<dt>Category<dt><dd>%s</dd>', $mailing->category());

printf('<dt>Subject line<dt><dd>%s</dd>', $mailing->subject());

echo '</dl>';

echo (new RichContentField('Body content', 'bulk_mail_body', true))
    ->setValue($mailing->body());