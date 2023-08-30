<h1>Bulk mail tools</h1>
<?php

use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\Sidebar\Sidebar;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Mailing;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

echo "<h2>Drafts</h2>";
printf('<a href="%s" class="button">Create blank bulk mailing</a>', new URL('_create.html'));

echo new PaginatedTable(
    BulkMail::drafts(),
    function (Mailing $mailing): array {
        return [
            $mailing->editUrl()->html(),
            $mailing->body() ? sprintf('<a href="%s">preview</a>', $mailing->previewUrl()) : '',
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
        '',
        'Created',
        'By',
        'Updated',
        'By',
        'Sent',
        'By'
    ]
);

// look for and surface relevant past mailings from this time last year/semester

// this time last equivalent semester
Sidebar::add(function (): string|null {
    $last_semester = Semesters::currentFull()->previous(3);
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
            $start->getTimestamp()
        )->where(
            'sent < ?',
            $end->getTimestamp()
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
                ];
            }
        )
    );
});

// this time last main semester
Sidebar::add(function (): string|null {
    if (Semesters::current()->semester() == "Summer") return null;
    $last_semester = Semesters::currentFull()->previousFull();
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
            $start->getTimestamp()
        )->where(
            'sent < ?',
            $end->getTimestamp()
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
                ];
            }
        )
    );
});