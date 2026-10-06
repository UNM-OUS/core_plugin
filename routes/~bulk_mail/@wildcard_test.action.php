<h1></h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\TEXTAREA;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Notifications;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing || !$mailing->sent())
    throw new HttpError(404);
include __DIR__ . '/_actions.include.php';

printf('<h1>Test: %s</h1>', $mailing->name());
Breadcrumb::setTopName($mailing->name());

Notifications::printNotice('This tool will immediately schedule a mailing of this message to be sent to only the recipients entered below.');

$form = new FormWrapper();
$form->button()->setText('Send test mailing');

$recipients = (new Field('Emails (one per line)', new TEXTAREA()))
    ->addTip('Duplicates will be automatically removed, including duplicates that are already included in the sources above.')
    ->addTip('Lines beginning with <kbd>#</kbd> are ignored, and can be used as comments.')
    ->addTip('Blank lines are ignored.')
    ->setDefault($mailing->extraRecipients())
    ->addForm($form);

if ($form->ready()) {
    // create a copy of the mailing and strip extra recipients
    // also prepend "TEST: " to subject and name
    $copy = $mailing->copy();
    $copy->setExtraRecipients($recipients->value());
    $copy->setSubject('TEST: ' . $copy->subject());
    $copy->setName('TEST: ' . $copy->name());
    $copy->update();
    // strip recipient sources straight in database because that's the only way
    DB::query()->update(
        'bulk_mail',
        [
            'sources'    => '',
            'updated'    => time(),
            'updated_by' => Session::uuid(),
        ],
        $copy->id(),
    )->execute();
    // load fresh copy of mailing from database
    $copy = BulkMail::mailing($copy->id());
    // send, flash confirmation and refresh
    $copy->send();
    Notifications::flashConfirmation('Queued test message. It may take a few minutes to actually send messages. Recipients: ' . count($copy->extraRecipientAddresses()));
    throw new RedirectException($mailing->editUrl());
}

echo $form;