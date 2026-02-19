<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing)
    throw new HttpError(404);
include __DIR__ . '/_actions.include.php';

$mailing = $mailing->copy();

Notifications::flashConfirmation('Copied ' . $mailing->name());
throw new RedirectException(new URL('./'));
