<h1>Permalinks</h1>
<p>
    Permalinks are a way to create short, shareable URLs that redirect to other URLs.
    They can be useful for sharing URLs that are long or which may change after being shared.
    They also count how many times they are used, which can be useful by itself.
</p>
<?php

use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnUserFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Permalinks\Permalink;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Permalinks\Permalinks;

$table = new PaginatedTable(
    Permalinks::select()
        ->order('updated DESC'),
    function (Permalink $pl): array {
        // TODO: editing tool, which includes delete/reset options
        return [
            implode('<br>', [
                sprintf('<a href="%s">%s</a>', $pl->url(), $pl->slug()),
                sprintf('<a href="%s">QR</a>', new URL('qr:' . $pl->slug()))
            ]),
            $pl->target(),
            $pl->count(),
            Format::date($pl->created()),
            $pl->createdBy(),
            Format::date($pl->updated()),
            $pl->updatedBy(),
        ];
    },
    [
        new ColumnStringFilteringHeader('Slug', 'slug'),
        new ColumnStringFilteringHeader('Target', 'target'),
        new ColumnSortingHeader('Count', 'count'),
        new ColumnDateFilteringHeader('Created', 'created'),
        new ColumnUserFilteringHeader('Created by', 'created_by'),
        new ColumnDateFilteringHeader('Updated', 'updated'),
        new ColumnUserFilteringHeader('Updated by', 'updated_by'),
    ]
);

echo $table;
