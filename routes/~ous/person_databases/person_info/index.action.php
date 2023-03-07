<h1>Person info database</h1>
<p>
    This database holds a wide variety of information about people, regardless of their UNM affiliation.
    It's mostly used for storing form pre-filling data, name preferences, and things like that.
</p>
<?php

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\EmailOrNetIDInput;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

$form = new FormWrapper();
$form->button()->setText('Add/update person');
$form->addClass('inline-form');

$identifier = (new Field('', new EmailOrNetIDInput()))
    ->setRequired(true);
$form->addChild($identifier);

if ($form->ready()) {
    throw new RedirectException(new URL('record:' . $identifier->value()));
}

echo "<h2>Add/update person by NetID or email</h2>";
echo $form;

echo "<h2>All current data</h2>";

$query = SharedDB::query()->from('person_info')
    ->order('updated DESC');

$table = new PaginatedTable(
    $query,
    function (array $row): array {
        $data = json_decode($row['data'], true, 512, JSON_THROW_ON_ERROR);
        return [
            sprintf('<a href="%s">%s</a>', new URL('record:' . $row['identifier']), $row['identifier']),
            @$data['firstname'],
            @$data['lastname'],
            @$data['fullname'],
            @$data['email'],
            Format::filesize(strlen($row['data']), 0),
            Format::date($row['updated']),
        ];
    },
    [
        new ColumnStringFilteringHeader('Identifer', 'identifier'),
        'First name',
        'Last name',
        'Full name',
        'Email',
        'Data',
        new ColumnDateFilteringHeader('Updated', 'updated'),
    ]
);

echo $table;
