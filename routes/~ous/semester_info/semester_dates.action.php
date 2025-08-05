<h1>Semester date management</h1>
<p>
    Use this page to configure the global semester start dates. Changes here will impact all OUS sites. Note that sites
    have different offsets configured, so no matter what you enter here, some sites will begin their own semesterly
    switch-overs on different dates.
</p>
<?php

use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

$dates = SharedDB::query()
    ->from('semester_dates')
    ->order('year desc, month desc, day desc');

echo new PaginatedTable(
    $dates,
    function (array $row): array {
        $date = new DateTime();
        $date->setTimezone(Format::timezone());
        $date->setDate($row['year'], $row['month'], $row['day']);
        $date->setTime(0, 0);
        return [
            $row['year'],
            $row['semester'],
            Format::date($date),
        ];
    },
    [
        'Year',
        'Semester',
        'Start date',
    ]
);

// TODO: some way of updating/adding these