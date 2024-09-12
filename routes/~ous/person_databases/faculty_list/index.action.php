<h1>Faculty list</h1>
<?php

use DigraphCMS\UI\Pagination\ColumnBooleanFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

$query = SharedDB::query()
    ->from('faculty_list')
    ->order('time desc')
    ->order('last_name, first_name');

$table = new DigraphCMS\UI\Pagination\PaginatedTable(
    $query,
    function (array $row): array {
        return [
            $row['first_name'],
            $row['last_name'],
            $row['org'],
            $row['department'],
            $row['title'],
            $row['rank'],
            $row['voting'] ? 'Yes' : 'No',
            $row['hsc'] ? 'Yes' : 'No',
            $row['branch'] ? 'Yes' : 'No',
            $row['research'] ? 'Yes' : 'No',
            $row['visiting'] ? 'Yes' : 'No',
            $row['netid'],
            $row['email']
        ];
    },
    [
        new ColumnStringFilteringHeader('First name', 'first_name'),
        new ColumnStringFilteringHeader('Last name', 'last_name'),
        new ColumnStringFilteringHeader('Level 3 org', 'org'),
        new ColumnStringFilteringHeader('Department', 'department'),
        new ColumnStringFilteringHeader('Title', 'title'),
        new ColumnStringFilteringHeader('Rank', 'rank'),
        new ColumnBooleanFilteringHeader('Voting', 'voting'),
        new ColumnBooleanFilteringHeader('HSC', 'hsc'),
        new ColumnBooleanFilteringHeader('Branch', 'branch'),
        new ColumnBooleanFilteringHeader('Research', 'research'),
        new ColumnBooleanFilteringHeader('Visiting', 'visiting'),
        new ColumnStringFilteringHeader('NetID', 'netid'),
        new ColumnStringFilteringHeader('Email', 'email'),
    ]
);

echo $table;
