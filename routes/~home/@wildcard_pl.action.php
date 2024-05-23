<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\ArbitraryRedirectException;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Permalinks\Permalinks;

$permalink = Permalinks::get(Context::url()->actionSuffix());

if (!$permalink) {
    throw new HttpError(404, 'Permalink not found');
}

$permalink->increment();

// TODO: Check with wayback machine?

throw new ArbitraryRedirectException($permalink->target());
