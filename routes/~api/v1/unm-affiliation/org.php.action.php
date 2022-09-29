<?php

use DigraphCMS\Context;
use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

if (Context::arg('csrf') !== Cookies::csrfToken('autocomplete')) {
    throw new HttpError(401);
}

Context::response()->enableCache();
Context::response()->filename('response.json');

// get a list of organizations
$column = AbstractMappedSelect::parseJsonRefs('${data.affiliation.org}');
$query = SharedDB::query()->from('person_info')
    ->disableSmartJoin()
    ->select("$column as org", true)
    ->where("$column is not null AND $column <> ?", [''])
    ->groupBy("$column")
    ->order("$column ASC");

if (Context::arg('query')) {
    $query->where('org LIKE ?', '%' . Context::arg('query') . '%');
}

$otherExists = false;
$queryExists = false;

$results = array_map(
    function (array $row) use (&$otherExists, &$queryExists): array {
        if ($row['org'] == 'Other') $otherExists = true;
        if ($row['org'] == Context::arg('query')) $queryExists = true;
        return [
            'html' => sprintf('<div class="title">%s</div>', $row['org']),
            'value' => $row['org'],
        ];
    },
    $query->fetchAll()
);

if (!$otherExists) {
    $results[] = [
        'html' => '<div class="title">Other</div>',
        'value' => 'Other',
    ];
}

if (!$queryExists && Context::arg('query') && Permissions::inMetaGroup('unmaffiliation__edit')) {
    $results[] = [
        'html' => sprintf('<div class="title">%s</div><div class="note">Edit: Add this value to the system</div>', Context::arg('query')),
        'value' => Context::arg('query'),
    ];
}

echo json_encode($results);
