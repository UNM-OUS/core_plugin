<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\AffiliatedNetIdAutocomplete;

if (Context::arg('csrf') !== Cookies::csrfToken('autocomplete')) {
    throw new HttpError(401);
}

Permissions::requireMetaGroup('users__query');

Context::response()->private(true);
Context::response()->filename('response.json');

echo json_encode(
    AffiliatedNetIdAutocomplete::search(
        Context::arg('query'),
        intval(Context::arg('include'))
    )
);