<h1>Staff list</h1>
<?php

use DigraphCMS\UI\Pagination\ColumnBooleanFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

$query = SharedDB::query()
    ->from('staff_list')
    ->order('time desc')
    ->order('last_name, first_name');

$table = new PaginatedTable(
    $query,
    function (array $row): array {
        return [
            $row['first_name'],
            $row['last_name'],
            $row['org'],
            $row['department'],
            $row['title'],
            $row['hsc'] ? 'Yes' : 'No',
            $row['branch'] ? 'Yes' : 'No',
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
        new ColumnBooleanFilteringHeader('HSC','hsc'),
        new ColumnBooleanFilteringHeader('Branch','branch'),
        new ColumnStringFilteringHeader('NetID', 'netid'),
        new ColumnStringFilteringHeader('Email', 'email'),
    ]
);

echo $table;
