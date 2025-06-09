<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\DateTimeInput;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\TEXTAREA;
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
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Mailing;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients\AbstractRecipientSource;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing || $mailing->sent()) throw new HttpError(404);

printf('<h1>Send: %s</h1>', $mailing->name());
Breadcrumb::setTopName($mailing->name());
include __DIR__ . '/_actions.include.php';
assert($mailing instanceof Mailing);

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

// schedule tab
// used to schedule a single sending of this mailing
$tabs->addTab('schedule', 'Schedule single', function () use ($mailing) {
    $form = new FormWrapper();
    $form->button()->setText('Schedule mailing');
    $datetime = (new DatetimeField('Scheduled time'))
        ->setDefault($mailing->scheduled())
        ->setRequired(true)
        ->addValidator(function (DateTimeInput $field): ?string {
            if ($field->value()->getTimestamp() < time()) {
                return 'Scheduled time must be in the future';
            }
            return null;
        })
        ->addForm($form);
    if ($form->ready()) {
        $mailing = $mailing->copy();
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
    Notifications::printNotice('Sending process will begin as soon as possible after the given time, and may take some time to complete (as long as a few hours for large mailings).');
    Notifications::printNotice('Recipient lists will be rebuilt before sending, so any automatically-generated mailing lists will use the latest data at the time of sending.');
});

// multi-schedule tab
// used to schedule multiple copies on a given list of dates/times
$tabs->addTab('multi', 'Multi-schedule', function () use ($mailing) {
    $form = new FormWrapper();
    $form->button()->setText('Schedule multiple mailings');
    $times = (new Field('List dates/times', new TEXTAREA))
        ->addTip('Enter a list of dates and times, one per line, most common formats are supported.')
        ->addTip('The entered times do not need to be in any particular order.')
        ->addTip('The system will warn you if it cannot parse a date/time.')
        ->addTip('You will be shown a list of what it interprets your inputs as before scheduling.')
        ->setRequired(true)
        ->addForm($form);
    $interpreted_times = preg_split('/\r\n|\r|\n/', $times->value());
    $interpreted_times = array_map('trim', $interpreted_times); // @phpstan-ignore-line it's fine
    $interpreted_times = array_filter($interpreted_times, fn($e) => !empty($e));
    $interpreted_times = array_map(
        function (string $in) use ($form) {
            try {
                $output = new DateTime($in, Format::timezone());
            } catch (\Throwable $th) {
                $form->addChild(sprintf(
                    '<div class="notification notification--warning">Error parsing line: %s</div>',
                    htmlspecialchars($in)
                ));
                return false;
            }
            if ($output->getTimestamp() < time()) {
                $form->addChild(sprintf(
                    '<div class="notification notification--warning">Warning: line %s is in the past and will be ignored.</div>',
                    htmlspecialchars($in)
                ));
                return false; // ignore past dates
            }
            return $output;
        },
        $interpreted_times
    );
    /** @var DateTime[] */
    $interpreted_times = array_filter($interpreted_times);
    $interpreted_times = array_unique($interpreted_times);
    sort($interpreted_times);
    foreach ($interpreted_times as $time) {
        $form->addChild(sprintf(
            '<div class="notification notification--confirmation">%s</div>',
            Format::datetime($time)
        ));
    }
    if ($form->submitted()) {
        (new CheckboxField('Confirm scheduling the dates shown above'))
            ->setRequired(true)
            ->addForm($form);
    }
    if ($form->ready()) {
        foreach ($interpreted_times as $time) {
            $mailing_copy = $mailing->copy();
            DB::query()->update(
                'bulk_mail',
                [
                    'scheduled' => $time->getTimestamp(),
                    'updated' => time(),
                    'updated_by' => Session::uuid()
                ],
                $mailing_copy->id()
            )->execute();
        }
        Notifications::flashConfirmation("Scheduled " . count($interpreted_times) . " mailings");
        throw new RedirectException(new URL('./'));
    }
    echo $form;
});

// send now tab
// used for sending immediately
$tabs->addTab('now', 'Send now', function () use ($mailing) {
    echo '<p>Click the button below to send a copy of this mailing immediately. This action cannot be undone. It may take some time for all messages to actually send from the mailing queue.</p>';
    echo (new CallbackLink(function () use ($mailing) {
        $mailing = $mailing->copy();
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
