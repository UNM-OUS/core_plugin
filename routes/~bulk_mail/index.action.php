<h1>Bulk mail tools</h1>
<?php

use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\Sidebar\Sidebar;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Mailing;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

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

// look for and surface relevant past mailings from this time last year

// this time last equivalent semester
Sidebar::add(function (): string {
    $last_semester = Semesters::current()->previous(3);
    $relevant = BulkMail::mailings();
    $other_day = $start = Semesters::transferTime(
        time(),
        $last_semester,
        Semesters::current(),
    );
    $start = (clone $other_day)->sub(new DateInterval('P1W'));
    $end = (clone $other_day)->add(new DateInterval('P2W'));
    $relevant
        ->where(
            'sent > ?',
            $start->getTimestamp(),
        )->where(
            'sent < ?',
            $end->getTimestamp(),
        );
    // if ($relevant->count() == 0) return null;
    return sprintf(
        '<h1>This time %s</h1><div class="small">%s to %s</div>%s',
        $last_semester,
        Format::date($start),
        Format::date($end),
        new PaginatedTable(
            $relevant,
            function (Mailing $mailing): array {
                return [
                    $mailing->previewUrl()->html(),
                    sprintf('<a href="%s">copy</a>', $mailing->copyUrl()),
                    sprintf('<a href="%s">source</a>', $mailing->sourceUrl()),
                ];
            }
        ),
    );
});
