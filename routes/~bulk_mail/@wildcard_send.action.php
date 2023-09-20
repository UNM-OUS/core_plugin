<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients\AbstractRecipientSource;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing || $mailing->sent()) throw new HttpError(404);

printf('<h1>Send: %s</h1>', $mailing->name());
Breadcrumb::setTopName($mailing->name());
include __DIR__ . '/_actions.include.php';

// TODO: option to switch back and forth between sending now or scheduling
echo (new CallbackLink(function () use ($mailing) {
    $job = $mailing->send();
    throw new RedirectException(new URL('?job=' . $job?->group()));
}))
    ->addClass('button button--warning')
    ->addChild('Send bulk mailing now');

echo "<h2>Recipient lists</h2>";
echo new PaginatedTable(
    $mailing->sources(),
    function (AbstractRecipientSource $source) {
        return [
            '<span class="notification notification--confirmation">' . $source->label() . '</span>',
            '~' . number_format($source->count()),
        ];
    }
);

echo "<h2>Extra recipients</h2>";
echo new PaginatedTable(
    $mailing->extraRecipientAddresses(),
    function (string $address): array {
        return [$address];
    },
    [
        'Email'
    ]
);
