<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\TabInterface;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients\AbstractRecipientSource;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing || $mailing->sent()) throw new HttpError(404);

printf('<h1>Send: %s</h1>', $mailing->name());
Breadcrumb::setTopName($mailing->name());
include __DIR__ . '/_actions.include.php';

// check for problems
if (!$mailing->body()) {
    Notifications::printError("Can't send, message has no content");
    return;
}

//  tab interface to switch back and forth between sending now or scheduling
$tabs = new TabInterface();
// possible tab to clear schedule
if ($mailing->scheduled()) {
    $tabs->addTab('scheduled', 'Schedule', function () use ($mailing) {
        Notifications::printConfirmation(sprintf(
            'This mailing is scheduled for %s',
            Format::datetime($mailing->scheduled())
        ));
        echo (new CallbackLink(function () use ($mailing) {
            DB::query()->update(
                'bulk_mail',
                [
                    'scheduled' => null,
                    'updated' => time(),
                    'updated_by' => Session::uuid()
                ],
                $mailing->id()
            )->execute();
            throw new RefreshException();
        }))
            ->addClass('button button--warning')
            ->addChild('Cancel schedule');
    });
}

// schedule tab is default
$tabs->addTab('schedule', 'Schedule sending', function () use ($mailing) {
    $form = new FormWrapper();
    $form->button()->setText('Schedule mailing');
    $datetime = (new DatetimeField('Scheduled time'))
        ->setDefault($mailing->scheduled())
        ->setRequired(true)
        ->addForm($form);
    if ($form->ready()) {
        DB::query()->update(
            'bulk_mail',
            [
                'scheduled' => $datetime->value()->getTimestamp(),
                'updated' => time(),
                'updated_by' => Session::uuid()
            ],
            $mailing->id()
        )->execute();
        Notifications::flashConfirmation("Scheduled mailing");
        throw new RedirectException(new URL('./'));
    }
    echo $form;
    Notifications::printNotice('Sending process will begin as soon as possible after this time, and may take some time to complete (as long as a few hours for large mailings).');
    Notifications::printNotice('Recipient lists will be rebuilt before sending, so any automatically-generated mailing lists will use the latest data at the time of sending.');
});
// send now tab
$tabs->addTab('now', 'Send now', function () use ($mailing) {
    echo (new CallbackLink(function () use ($mailing) {
        $job = $mailing->send();
        throw new RedirectException(new URL('messages:' . $mailing->id() . '?job=' . $job->group()));
    }))
        ->addClass('button button--warning')
        ->addChild('Send bulk mailing now');
});
echo $tabs;

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
