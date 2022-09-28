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

// get a list of departments
$column = AbstractMappedSelect::parseJsonRefs('${data.affiliation.department}');
$query = SharedDB::query()->from('person_info')
    ->disableSmartJoin()
    ->select("$column as department", true)
    ->where("department is not null AND department <> ?", [''])
    ->groupBy("department")
    ->order("department ASC");

if (Context::arg('org')) {
    $query->where(AbstractMappedSelect::parseJsonRefs('${data.affiliation.org}'), Context::arg('org'));
}

if (Context::arg('query')) {
    $query->where('department LIKE ?', '%' . Context::arg('query') . '%');
}

$otherExists = false;
$queryExists = false;

$results = array_map(
    function (array $row) use (&$otherExists, &$queryExists): array {
        if ($row['department'] == 'Other') $otherExists = true;
        if ($row['department'] == Context::arg('query')) $queryExists = true;
        return [
            'html' => sprintf('<div class="title">%s</div>', $row['department']),
            'value' => $row['department'],
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
        'html' => sprintf('<div class="title">%s</div><div class="note">Editor option: Add this value to the system</div>', Context::arg('query')),
        'value' => Context::arg('query'),
    ];
}

echo json_encode($results);
