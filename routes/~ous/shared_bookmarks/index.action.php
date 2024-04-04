<h1>Shared bookmarks</h1>
<p>
    This area administers shared bookmarks, which allow all OUS sites to share a common set of bookmarks.
    They are difficult to remove by design to avoid breaking links on other sites, and must be deleted manually in the database.
    Many are automatically regenerated from outside sources in the background periodically, and manual changes may get overwritten by that process.
</p>
<?php

use DigraphCMS\UI\Pagination\ColumnBooleanFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedBookmarks\SharedBookmark;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedBookmarks\SharedBookmarks;

$bookmarks = SharedBookmarks::select()
    ->orderBy('category')
    ->orderBy('name');

$table = new PaginatedTable(
    $bookmarks,
    function (SharedBookmark $bookmark) {
        return [
            $bookmark->category(),
            $bookmark->name(),
            $bookmark->title(),
            $bookmark->url(),
            $bookmark->searchable() ? 'Yes' : 'No',
            sprintf('<code>%s</code>', $bookmark->tag()),
        ];
    },
    [
        new ColumnStringFilteringHeader('Category', 'category'),
        new ColumnStringFilteringHeader('Name', 'name'),
        new ColumnStringFilteringHeader('Title', 'title'),
        new ColumnStringFilteringHeader('URL', 'url'),
        new ColumnBooleanFilteringHeader('Searchable', 'searchable', 'Yes', 'No'),
        'Example Shortcode',
    ]
);

$table->download(
    'shared bookmarks',
    function (SharedBookmark $bookmark) {
        return [
            $bookmark->category(),
            $bookmark->name(),
            $bookmark->title(),
            $bookmark->url(),
            $bookmark->searchable() ? 'Yes' : 'No',
        ];
    },
    [
        'Category',
        'Name',
        'Title',
        'URL',
        'Searchable'
    ]
);

echo $table;
