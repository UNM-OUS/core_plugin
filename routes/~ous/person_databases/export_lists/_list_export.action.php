<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Media\File;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\TabInterface;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OpinioExporter;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use Envms\FluentPDO\Queries\Select;

$query = SharedDB::query();
switch (Context::arg('type')) {
    case 'voting_faculty':
        $query = $query->from('faculty_list')
            ->where('voting');
        break;
    case 'all_faculty':
        $query = $query->from('faculty_list');
        break;
    case 'staff':
        $query = $query->from('staff_list');
        break;
    default:
        throw new HttpError(400, 'Invalid type parameter');
}
assert($query instanceof Select);

if (Context::arg('org') && !in_array(Context::arg('org'), ['Other'])) {
    $query->where('org', Context::arg('org'));
    if (!$query->count()) throw new HttpError(404, 'Org not found');
}

if (Context::arg('department')) {
    $query->where('department', Context::arg('department'));
    if (!$query->count()) throw new HttpError(404, 'Department not found');
}

echo '<div id="list-export-interface">';

$tabs = new TabInterface();

/**
 * Output as spreadsheet, which can be exported to Excel
 */
$tabs->addTab('excel', 'Spreadsheet', function () use ($query) {
    $table = new PaginatedTable(
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
                $row['email'],
            ];
        },
        [
            'First name',
            'Last name',
            'Level 3 org',
            'Department',
            'Title',
            'Rank',
            'Voting',
            'HSC',
            'Branch',
            'Research',
            'Visiting',
            'NetID',
            'Email',
        ]
    );

    $table->download(
        implode(' - ', array_filter([
            Context::arg('type'),
            Context::arg('org'),
            Context::arg('department'),
            date('Y-m-d')
        ])),
        null,
        [
            'First name',
            'Last name',
            'Level 3 org',
            'Department',
            'Title',
            'Rank',
            'Voting',
            'HSC',
            'Branch',
            'Research',
            'Visiting',
            'NetID',
            'Email',
        ]
    );

    echo $table;
});

/**
 * Output as email list that is ready to copy/paste into an email client
 */
$tabs->addTab('email', 'Email list', function () use ($query) {
    $emails = [];
    while ($row = $query->fetch()) {
        $email = $row['email'];
        $name = sprintf('%s %s', $row['first_name'], $row['last_name']);
        if (!$email) continue;
        $emails[$email] = sprintf('%s <%s>', json_encode($name), $email);
    }
    $emails = array_unique(array_filter($emails));
    echo '<pre>';
    echo implode(';' . PHP_EOL, array_map(htmlentities(...), $emails));
    echo '</pre>';
});

/**
 * Output as email list that is ready to copy/paste into a listserv
 */
$tabs->addTab('listserv', 'Listserv list', function () use ($query) {
    $emails = [];
    while ($row = $query->fetch()) {
        $email = $row['email'];
        $name = sprintf('%s %s', $row['first_name'], $row['last_name']);
        if (!$email) continue;
        $emails[$email] = sprintf('%s %s', $email, $name);
    }
    $emails = array_unique(array_filter($emails));
    $file = new File(
        sprintf(
            '%s LISTSERV.txt',
            implode(' - ', array_filter([
                Context::arg('type'),
                Context::arg('org'),
                Context::arg('department'),
                date('Y-m-d')
            ])),
        ),
        implode(PHP_EOL, $emails)
    );
    printf(
        '<p><a href="%s" class="button">Download LISTSERV list</a></p>',
        $file->url()
    );
});

/**
 * Output for opinio
 */
$tabs->addTab('opinio', 'Opinio', function () use ($query) {
    $data = [
        [
            'Name',
            'Email',
            'NetID',
            'Level 3 org',
            'Department',
            'Title',
            'Rank',
            'Voting',
            'HSC',
            'Branch',
            'Research',
            'Visiting',
        ]
    ];
    while ($row = $query->fetch()) {
        $data[] = [
            'Name' => sprintf('%s %s', $row['first_name'], $row['last_name']),
            'Email' => $row['email'],
            'NetID' => $row['netid'],
            'Level 3 org' => $row['org'],
            'Department' => $row['department'],
            'Title' => $row['title'],
            'Rank' => @$row['rank'],
            'Voting' => $row['voting'] ? 'Yes' : 'No',
            'HSC' => $row['hsc'] ? 'Yes' : 'No',
            'Branch' => $row['branch'] ? 'Yes' : 'No',
            'Research' => $row['research'] ? 'Yes' : 'No',
            'Visiting' => $row['visiting'] ? 'Yes' : 'No',
        ];
    }
    $file = new File(
        sprintf(
            '%s OPINIO.csv',
            implode(' - ', array_filter([
                Context::arg('type'),
                Context::arg('org'),
                Context::arg('department'),
                date('Y-m-d')
            ])),
        ),
        OpinioExporter::array($data)
    );
    printf(
        '<p><a href="%s" class="button">Download Opinio list</a></p>',
        $file->url()
    );
});

echo $tabs;

echo '</div>';
