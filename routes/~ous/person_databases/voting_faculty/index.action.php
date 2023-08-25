<h1>Voting faculty</h1>
<?php

use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\People\FacultyInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

$query = SharedDB::query()->from('voting_faculty');

$table = new PaginatedTable(
    $query,
    function (array $row): array {
        return [
            $row['firstname'],
            $row['lastname'],
            $row['org'],
            $row['department'],
            $row['title'],
            $row['academic_title'],
            FacultyInfo::search($row['netid'])?->rank(),
            $row['netid'],
            $row['email']
        ];
    },
    [
        new ColumnStringFilteringHeader('First name', 'firstname'),
        new ColumnStringFilteringHeader('Last name', 'lastname'),
        new ColumnStringFilteringHeader('School/college', 'org'),
        new ColumnStringFilteringHeader('Department', 'department'),
        new ColumnStringFilteringHeader('Title', 'title'),
        new ColumnStringFilteringHeader('Academic title', 'academic_title'),
        'Rank (computed)',
        new ColumnStringFilteringHeader('NetID', 'netid'),
        new ColumnStringFilteringHeader('Email', 'email'),
    ]
);

$table->download(
    'Voting faculty',
    function (array $row) {
        return [
            $row['firstname'],
            $row['lastname'],
            $row['org'],
            $row['department'],
            $row['title'],
            $row['academic_title'],
            $row['netid'],
            $row['email']
        ];
    },
    [
        'First name',
        'Last name',
        'Level 3 org',
        'Department',
        'Title',
        'Academic title',
        'NetID',
        'Email',
    ]
);

echo $table;
