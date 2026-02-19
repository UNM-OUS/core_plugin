<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Emails;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Media\File;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Message;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\MessageSelect;

// try to find this message ID in bulk mail
$message = (new MessageSelect())
    ->where('email_message_id', Context::url()->actionSuffix())
    ->fetch();
if (!$message)
    throw new HttpError(404);
assert($message instanceof Message);
$mailing = $message->mailing();
Breadcrumb::parent(
    (new URL('messages:' . $mailing->id()))
        ->setName($mailing->subject()),
);

// make sure the message actually exists in the email system
$email = Emails::get(Context::url()->actionSuffix());
if (!$email) {
    Notifications::printNotice('This email is no longer in the database. The actual messages are only retained for 3-12 months, so this likely means the email is old and was cleaned up by a routine maintenance task.');
    return;
}

echo "<h1>Email: " . $email->subject() . "</h1>";

if ($email->error()) {
    echo "<h2>Send error</h2>";
    Notifications::printError($email->error());
}

echo "<h2>Metadata</h2>";

echo "<dl>";
printf("<dt>%s</dt><dd>%s</dd>", 'Created', Format::datetime($email->time()));
printf("<dt>%s</dt><dd>%s</dd>", 'Sent', $email->sent() ? Format::datetime($email->sent()) : '<em>pending</em>');
printf("<dt>%s</dt><dd>%s</dd>", 'To', $email->to());
$email->cc() ? printf("<dt>%s</dt><dd>%s</dd>", 'CC', $email->cc()) : '';
$email->bcc() ? printf("<dt>%s</dt><dd>%s</dd>", 'BCC', $email->bcc()) : '';
printf("<dt>%s</dt><dd>%s</dd>", 'From', $email->from());
echo "</dl>";

$file = new File(
    $email->uuid() . '.html',
    Emails::prepareBody_html($email),
    [
        'email_message_admin_view',
        $email->uuid(),
    ],
    function () {
        return Permissions::inMetaGroup('bulkmail__edit');
    }
);

echo "<h2>Rendered HTML</h2>";
echo "<iframe src='" . $file->url() . "' style='border:0;width:100%;' class='autosized-frame'></iframe>";

echo "<h2>Rendered plaintext</h2>";
$text = Emails::prepareBody_text($email);
echo "<pre>" . $text . "</pre>";
