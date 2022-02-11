<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\UI\Format;

$query = DB::query()->from('user_source')
    ->where('user_uuid = ?', [Context::url()->action()])
    ->where('source = "cas" AND provider = "netid"');

if ($query->count()) {
    printf(
        "<h2>UNM NetID%s</h2>",
        $query->count() > 1 ? 's' : ''
    );
    echo "<ul>";
    foreach ($query->execute() as $row) {
        printf(
            "<li><strong><code>%s</code></strong> <small>%s</small></li>",
            strtoupper($row['provider_id']),
            Format::date($row['created'])
        );
    }
    echo "</ul>";
}
