<h1>Bulk mail tools</h1>
<?php

use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Mailing;

echo "<h2>Templates</h2>";
printf('<a href="%s" class="button">Create bulk mail template</a>', new URL('_create.html'));

echo new PaginatedTable(
    BulkMail::templates(),
    function (Mailing $mailing): array {
        return [
            $mailing->editUrl()->html(),
            sprintf('<a href="%s">preview</a>', $mailing->previewUrl()),
            sprintf('<a href="%s">recipients</a>', $mailing->recipientsUrl()),
            sprintf('<a href="%s">schedule</a>', $mailing->scheduleUrl()),
            Format::date($mailing->updated()),
            sprintf('<a href="%s">delete</a>', $mailing->deleteUrl()),
        ];
    },
    [
        'Mailing',
        '',
        '',
        '',
        'Updated',
        '',
    ],
);

echo "<h2>Send log</h2>";
echo new PaginatedTable(
    BulkMail::mailings(),
    function (Mailing $mailing): array {
        return [
            $mailing->previewUrl()->html(),
            sprintf('<a href="%s">messages (%s)</a>', $mailing->messagesUrl(), $mailing->messageCount()),
            sprintf('<a href="%s">source</a>', $mailing->sourceUrl()),
            Format::date($mailing->sent()),
        ];
    },
    [
        new ColumnStringFilteringHeader('Mailing', 'name'),
        '',
        '',
        'Sent',
    ],
);
