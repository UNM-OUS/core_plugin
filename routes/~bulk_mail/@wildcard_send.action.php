<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\DB\DB;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnUserFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Message;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing || $mailing->sent()) throw new HttpError(404);

printf('<h1>Send: %s</h1>', $mailing->name());
Breadcrumb::setTopName($mailing->name());
include __DIR__ . '/_actions.include.php';

echo (new CallbackLink(function () use ($mailing) {
    DB::query()->update('bulk_mail', [
        'sent' => time(),
        'sent_by' => Session::uuid(),
    ], $mailing->id())->execute();
    $id = $mailing->id();
    $job = new DeferredJob(function (DeferredJob $job) use ($id) {
        $mailing = BulkMail::mailing($id);
        $messages = DB::query()->from('bulk_mail_message')
            ->where('bulk_mail_id', $mailing->id())
            ->where('sent is null');
        while ($message = $messages->fetch()) {
            $id = intval($message['id']);
            $job->spawn(function () use ($id) {
                $message = BulkMail::message($id);
                $mailing = $message->mailing();
                Context::beginEmail();
                Context::fields()['bulk_mail'] = [
                    'email' => $message->email(),
                    'user' => $message->user()
                ];
                $email = new Email(
                    $mailing->category(),
                    $mailing->subject(),
                    $message->email(),
                    $message->user() ? $message->user()->uuid() : null,
                    $mailing->from(),
                    new RichContent($mailing->body())
                );
                Emails::queue($email);
                DB::query()
                    ->update(
                        'bulk_mail_message',
                        [
                            'sent' => time(),
                            'email_uuid' => $email->uuid()
                        ],
                        $message->id()
                    )
                    ->execute();
                Context::end();
                return 'Built bulk message #' . $message->id();
            });
        }
        return 'Completed preparing bulk messages for "' . $mailing->name() . '"';
    });
    throw new RedirectException(new URL('messages:' . $id . '?job=' . $job->group()));
}))
    ->addClass('button button--warning')
    ->addChild('Send bulk mailing now');

echo "<h2>Recipient list</h2>";
echo new PaginatedTable(
    $mailing->messages(),
    function (Message $message): array {
        return [
            $message->email(),
            $message->user()
        ];
    },
    [
        new ColumnStringFilteringHeader('Email', 'email'),
        new ColumnUserFilteringHeader('Account', 'user')
    ]
);
