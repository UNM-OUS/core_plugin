<?php

use DigraphCMS\CodeMirror\CodeMirrorInput;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing) throw new HttpError(404);
include __DIR__ . '/_actions.include.php';

printf('<h1>Source: %s</h1>', $mailing->name());
Breadcrumb::setTopName($mailing->name());

echo '<dl>';

printf('<dt>From address<dt><dd>%s</dd>', $mailing->from());

printf('<dt>Category<dt><dd>%s</dd>', $mailing->category());

printf('<dt>Subject line<dt><dd>%s</dd>', $mailing->subject());

echo '</dl>';

$source = new CodeMirrorInput();
$source->setValue($mailing->body());
$source->setMode('gfm');
echo $source;