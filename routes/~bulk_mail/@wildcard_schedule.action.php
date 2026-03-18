<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\TEXTAREA;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Mailing;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing || $mailing->sent())
    throw new HttpError(404);

printf('<h1>Schedule: %s</h1>', $mailing->name());
Breadcrumb::setTopName($mailing->name());
include __DIR__ . '/_actions.include.php';
assert($mailing instanceof Mailing);

// check for problems
if (!$mailing->body()) {
    Notifications::printError("Can't send, message has no content");
    return;
}

// note that the add form's frame wraps the schedule too, so that the schedule updates from it
echo '<div class="navigation-frame navigation-frame--stateless" id="multi-schedule-form">';

// display upcoming scheduled send times
echo "<h2>Scheduled send times</h2>";
echo '<p>The following dates and times are scheduled to send this mailing. It will not send immediately at these times, but should begin building within an hour of them.</p>';
echo '<div class="navigation-frame navigation-frame--stateless" id="scheduled-times">';
$upcoming = $mailing->scheduledTimes();
$table = new PaginatedTable(
    $upcoming,
    function (int $time) use ($mailing): array {
        return [
            Format::datetime($time),
            (new CallbackLink(function () use ($time, $mailing) {
                $mailing->removeScheduledTime($time);
                $mailing->update();
                Notifications::flashConfirmation("Removed scheduled send time");
            }))
                ->setID('remove-' . $time)
                ->addChild('remove')
                ->setData('target', 'scheduled-times'),
        ];
    }
);
echo $table;
echo '</div>';

// multi-schedule form to add many dates/times at once
// used to schedule multiple copies on a given list of dates/times
echo '<h2>Add dates/times to schedule</h2>';
echo '<p>This button will schedule more upcoming dates and times for this mailing. A variety of formats are supported, and it will confirm its interpretation of what you enter before adding anything to the schedule.</p>';
$form = new FormWrapper();
$form->setData('target', 'multi-schedule-form');
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
        }
        catch (Throwable $th) {
            $form->addChild(sprintf(
                '<div class="notification notification--warning">Error parsing line: %s</div>',
                htmlspecialchars($in),
            ));
            return false;
        }
        if ($output->getTimestamp() < time()) {
            $form->addChild(sprintf(
                '<div class="notification notification--warning">Warning: line %s is in the past and will be ignored.</div>',
                htmlspecialchars($in),
            ));
            return false; // ignore past dates
        }
        return $output;
    },
    $interpreted_times,
);
/** @var DateTime[] */
$interpreted_times = array_filter($interpreted_times);
foreach ($interpreted_times as $time) {
    $form->addChild(sprintf(
        '<div class="notification notification--confirmation">%s</div>',
        Format::datetime($time),
    ));
}
if ($form->submitted()) {
    (new CheckboxField('Confirm scheduling the dates shown above'))
        ->setRequired(true)
        ->addForm($form);
}
if ($form->ready()) {
    foreach ($interpreted_times as $time) {
        $mailing->addScheduledTime($time);
    }
    $mailing->update();
    Notifications::flashConfirmationHTML("Scheduled " . count($interpreted_times) . " mailings");
    throw new RefreshException();
}
echo $form;
echo '</div>';

echo '<hr>';

echo '<h2>Send mailing immediately</h2>';
echo '<p>This button will create an immediate copy of this mailing, which will begin building and sending within a few minutes.</p>';
$link = (new CallbackLink(function () use ($mailing) {
    $mailing->copy()->send();
    Notifications::flashConfirmation("Scheduled immediate send of mailing");
    throw new RedirectException(new URL('./'));
}))
    ->addClass('button button--warning')
    ->addChild('Send mailing immediately');
echo $link;
