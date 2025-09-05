<h1>Upcoming scheduled mailings</h1>
<p>
    This page shows all the upcoming mailings currently scheduled.
</p>
<?php

use DigraphCMS\Spreadsheets\CellWriters\DateCell;
use DigraphCMS\Spreadsheets\CellWriters\LinkCell;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients\AbstractRecipientSource;
use Joby\Toolbox\Sorting\Sort;

$table_columns = ['Date', 'Template'];
$source_columns = [];
$schedule = [];
foreach (BulkMail::templates() as $mailing) {
    if (!$mailing->scheduledTimes()) continue;
    foreach ($mailing->sources() as $source) {
        $source_columns[$source->name()] = $source->label();
    }
    foreach ($mailing->scheduledTimes() as $mailing_time) {
        $schedule[] = [
            'time' => $mailing_time,
            'template' => $mailing
        ];
    }
}
asort($source_columns);
foreach ($source_columns as $source_name => $source_label) {
    $table_columns[] = $source_label;
}
Sort::sort($schedule, Sort::compareArrayValues('time'));

$table = new PaginatedTable(
    $schedule,
    function (array $r) use ($source_columns): array {
        $mailing = $r['template'];
        $time = $r['time'];
        return [
            Format::date($time),
            sprintf('<a href="%s">%s</a>', $mailing->previewUrl(), $mailing->name()),
            implode('<br>', array_map(fn(AbstractRecipientSource $s) => $s->label(), $mailing->sources())),
        ];
    },
    [
        'Date',
        'Template',
        'Recipients'
    ]
);
$table->paginator()->perPage(1000);

$table->download(
    'Upcoming scheduled mailings',
    function (array $r) use ($source_columns): array {
        $mailing = $r['template'];
        $time = $r['time'];
        $row = [
            new DateCell(Format::parseDate($time)),
            new LinkCell($mailing->name(), $mailing->previewUrl()),
        ];
        foreach ($source_columns as $source_name => $source_label) {
            foreach ($mailing->sources() as $source) {
                if ($source->name() == $source_name) {
                    $row[$source_name] = 'X';
                    break;
                }
            }
            $row[$source_name] ??= '';
        }
        return $row;
    },
    $table_columns,
);

echo $table;