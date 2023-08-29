<h1>Bulk mail tools</h1>
<?php

use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Mailing;

echo "<h2>Drafts</h2>";
printf('<a href="%s" class="button">Create blank bulk mailing</a>', new URL('_create.html'));

echo new PaginatedTable(
    BulkMail::drafts(),
    function (Mailing $mailing): array {
        return [
            $mailing->editUrl()->html(),
            $mailing->body() ? sprintf('<a href="%s" target="_blank">preview</a>', $mailing->previewUrl()) : '',
            $mailing->body() ? sprintf('<a href="%s">recipients (%s)</a>', $mailing->recipientsUrl(), $mailing->messageCount()) : '',
            $mailing->messageCount() ? sprintf('<a href="%s">send</a>', $mailing->sendUrl()) : '',
            Format::date($mailing->created()),
            $mailing->createdBy(),
            Format::date($mailing->updated()),
            $mailing->updatedBy(),
            sprintf('<a href="%s">delete</a>', $mailing->deleteUrl())
        ];
    },
    [
        'Mailing',
        '',
        '',
        '',
        'Created',
        'By',
        'Updated',
        'By',
        ''
    ]
);

echo "<h2>Sent mailings</h2>";

echo new PaginatedTable(
    BulkMail::mailings(),
    function (Mailing $mailing): array {
        return [
            $mailing->previewUrl()->html(),
            sprintf('<a href="%s">messages (%s)</a>', $mailing->messagesUrl(), $mailing->messageCount()),
            sprintf('<a href="%s">source</a>', $mailing->sourceUrl()),
            sprintf('<a href="%s">copy</a>', $mailing->copyUrl()),
            Format::date($mailing->created()),
            $mailing->createdBy(),
            Format::date($mailing->updated()),
            $mailing->updatedBy(),
            Format::date($mailing->sent()),
            $mailing->sentBy()
        ];
    },
    [
        'Mailing',
        '',
        '',
        'Created',
        'By',
        'Updated',
        'By',
        'Sent',
        'By'
    ]
);
