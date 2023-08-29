<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\HTML\Forms\TEXTAREA;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnUserFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Message;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients\AbstractRecipientSource;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing || !$mailing->sent()) throw new HttpError(404);
include __DIR__ . '/_action.include.php';

printf('<h1>Messages: %s</h1>', $mailing->name());
Breadcrumb::setTopName($mailing->name());

if ($job = Context::arg('job')) {
    echo (new DeferredProgressBar($job))
        ->setDisplayAfter('All messages built')
        ->setBounceAfter(new URL('?'));
}

// display of and recalculation tools for final list
echo "<h2>Final recipient list</h2>";
echo new PaginatedTable(
    $mailing->messages(),
    function (Message $message): array {
        $email = $message->emailMessage();
        if (!$email) $status = '';
        elseif ($email->error()) $status = '<span class="notification notification--error">error</span>';
        elseif ($email->sent()) $status = '<span class="notification notification--confirmation">sent ' . Format::date($email->sent()) . '</span>';
        else $status = '<span class="notification notification--notice">queued</span>';
        return [
            $message->email(),
            $message->user(),
            $message->sent() ? Format::date($message->sent()) : '',
            $email ? sprintf('<a href="%s">%s</a>', $email->url_adminInfo(), $email->uuid()) : '',
            $status
        ];
    },
    [
        new ColumnStringFilteringHeader('Email', 'email'),
        new ColumnUserFilteringHeader('Account', 'user'),
        'Built',
        'Email ID',
        'Status',
    ]
);

echo "<h2>Recipient sources</h2>";

// display selected sources
echo new PaginatedTable(
    $mailing->sources(),
    function (AbstractRecipientSource $source) {
        return [
            $source->name(),
            $source->label(),
            number_format($source->count()) . ' (current count)'
        ];
    }
);

// extra recipients list
echo "<h3>Extra email recipients</h3>";
echo (new TEXTAREA())
    ->setValue($mailing->extraRecipients());
