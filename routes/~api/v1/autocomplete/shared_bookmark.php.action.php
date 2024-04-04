<?php

use DigraphCMS\Context;
use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedBookmarks\SharedBookmark;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedBookmarks\SharedBookmarks;

if (Context::arg('csrf') !== Cookies::csrfToken('autocomplete')) {
    throw new HttpError(401);
}

Context::response()->private(true);
Context::response()->filename('response.json');

$bookmarks = [];

// get relatively strict name matches
$query = SharedBookmarks::select()
    // ->where('searchable', true)
    ->limit(100);
/** @var string $word */
foreach (preg_split('/ +/', Context::arg('query')) ?: [] as $word) {
    $query->whereOr('title LIKE ?', AbstractMappedSelect::prepareLikePattern($word, true, true));
}
$bookmarks = array_merge(
    $bookmarks,
    $query->fetchAll() // @phpstan-ignore-line
);

// score results
$bookmarks = array_map(
    function (SharedBookmark $bookmark) {
        return [
            $bookmark,
            SharedBookmarks::scoreSearchResult($bookmark, Context::arg('query'))
        ];
    },
    $bookmarks
);
// sort by score
usort(
    $bookmarks,
    function ($a, $b) {
        return $b[1] - $a[1];
    }
);
// strip back to just page object
$bookmarks = array_map(
    function ($arr) {
        return $arr[0];
    },
    $bookmarks
);

$bookmarks = array_unique($bookmarks, SORT_REGULAR);

$words = preg_split('/ +/', trim(Context::arg('query'))) ?: [];

echo json_encode(
    array_map(
        function (SharedBookmark $bookmark) use ($words) {
            $name = $bookmark->title();
            $url = $bookmark->url();
            foreach ($words as $word) {
                $word = preg_quote($word, '/');
                $name = preg_replace('/' . $word . '/i', '<strong>$0</strong>', $name);
            }
            return [
                'html' => '<div class="title">' . $name . '</div><div class="url">' . $url . '</div>',
                'value' => $bookmark->name(),
                'extra' => [
                    'category' => $bookmark->category(),
                ],
                'class' => 'page'
            ];
        },
        array_slice($bookmarks, 0, 50)
    )
);
