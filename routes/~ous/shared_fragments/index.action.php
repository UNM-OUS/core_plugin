<h1>Shared fragments</h1>
<p>
    This area administers shared fragments, which allow all OUS sites to share a common set of text fragments.
    Generally these are automatically generated in the background automatically, to do things like allowing all sites to use a single shortcode tag to represent common data like the date/time/locations of events.
    They are difficult to fully remove by design, to avoid breaking content on other sites, and must be deleted manually in the database.
</p>
<?php

use DigraphCMS\UI\Pagination\ColumnBooleanFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedFragments\SharedFragment;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedFragments\SharedFragments;

$fragments = SharedFragments::select()
    ->order('category')
    ->order('name');

$table = new PaginatedTable(
    $fragments,
    function (SharedFragment $fragment): array {
        $edit_url = new URL('_edit.html?id=' . $fragment->id());
        return [
            $fragment->category(),
            sprintf('<a href="%s">%s</a>', $edit_url, $fragment->name() ?: '<em>&lt;none&gt;</em>'),
            sprintf('<code>%s</code>', $fragment->tag()),
            strip_tags($fragment->value()),
            $fragment->searchable() ? 'Yes' : 'No',
        ];
    },
    [
        new ColumnStringFilteringHeader('Category', 'category'),
        new ColumnStringFilteringHeader('Name', 'name'),
        'Shortcode',
        new ColumnStringFilteringHeader('Value (HTML stripped)', 'value'),
        new ColumnBooleanFilteringHeader('Searchable', 'searchable', 'Yes', 'No'),
    ],
);

echo $table;