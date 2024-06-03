<h1>Delete permalink</h1>
<p>
    Are you sure you want to delete this permalink?
    This action cannot be undone.
</p>
<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Permalinks\Permalinks;

$pl = Permalinks::get(Context::url()->actionSuffix());

if (!$pl) {
    throw new HttpError(404, 'Permalink not found');
}

echo (new CallbackLink(function () {
    DB::query()
        ->delete('permalink')
        ->where('slug', Context::url()->actionSuffix())
        ->execute();
    Notifications::flashConfirmation('Permalink deleted');
    throw new RedirectException(new URL('./'));
}))
    ->addClass('button button--danger')
    ->addChild('Delete permalink');
