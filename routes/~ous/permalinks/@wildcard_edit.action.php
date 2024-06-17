<h1>Edit permalink destination</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\InputInterface;
use DigraphCMS\HTML\Forms\UrlInput;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\WaybackMachine;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Permalinks\Permalinks;

$pl = Permalinks::get(Context::url()->actionSuffix());
if (!$pl) {
    throw new HttpError(404, 'Permalink not found');
}

$form = new FormWrapper();

$url = (new Field('URL', new UrlInput()))
    ->setRequired(true)
    ->setDefault($pl->target())
    ->addForm($form);

if ($form->ready()) {
    // update
    DB::query()
        ->update('permalink')
        ->set([
            'target' => $url->value(),
            'updated' => time(),
            'updated_by' => Session::uuid()
        ])
        ->where('slug', $pl->slug())
        ->execute();
    // pre-emptively queue wayback check
    WaybackMachine::check($url->value());
    // notify and return
    Notifications::flashConfirmation('Permalink updated');
    throw new RedirectException(new URL('../permalinks/qr:' . $pl->slug()));
} else {
    echo $form;
}
