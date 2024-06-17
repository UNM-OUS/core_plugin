<h1>Create permalink</h1>
<?php

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\InputInterface;
use DigraphCMS\HTML\Forms\UrlInput;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\WaybackMachine;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Permalinks\Permalinks;

$form = new FormWrapper();

$url = (new Field('URL', new UrlInput()))
    ->setRequired(true)
    ->addForm($form);

$slug = (new Field('Custom slug (optional)'))
    ->addTip('This is the part of the URL that comes after <kbd>' . (new URL('/')) . 'pl:</kbd>.')
    ->addTip('It must be unique, and will be stripped down to a set of valid characters if necessary.')
    ->addValidator(function (InputInterface $input): string|null {
        $slug = $input->value();
        if (!$slug) return null;
        $slug = Permalinks::cleanSlug($slug);
        if (!$slug) return "Slug could not be cleaned up into a valid value";
        if (Permalinks::get($slug)) return "Slug is already in use";
        return null;
    })
    ->addForm($form);

if ($form->ready()) {
    $permalink = Permalinks::create($url->value(), $slug->value());
    // pre-emptively queue wayback check
    WaybackMachine::check($permalink->target());
    // notify and return
    Notifications::flashConfirmation('Permalink created');
    throw new RedirectException(new URL('../permalinks/qr:' . $permalink->slug()));
} else {
    echo $form;
}
