<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Media\File;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));

Context::response()->template('null.php');

// begin a new context with mock bulk_mail field so that tags will render
Context::beginEmail();
Context::fields()['bulk_mail'] = [
    'email' => 'nobody@localhost',
    'user' => null
];

$email = new Email(
    $mailing->category(),
    $mailing->subject(),
    'nobody@localhost',
    null,
    $mailing->from(),
    new RichContent($mailing->body())
);

$file = new File(
    'preview.html',
    Emails::prepareBody_html($email),
    [
        'bulk_mail_preview',
        $mailing->id(),
    ]
);
printf('<iframe src="%s" class="autosized_frame"></iframe>', $file->url());

Context::end();