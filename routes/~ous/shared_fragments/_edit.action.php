<h1>Edit shared fragment</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\TEXTAREA;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedFragments\SharedFragments;

$fragment = SharedFragments::getById(Context::arg_int('id'));
if (!$fragment)
    throw new HttpError(404);

Notifications::printWarningHTML('<strong>Note:</strong> many shared fragments are automatically generated, and if this is one of them your changes will be automatically overwritten next time the background tasks that produced it run again. Changes may also take some time to propagate to all cached copies of all content across all sites. Please be patient as changes may take as long as 24 hours to update everywhere in extreme cases.');

$form = new FormWrapper();
$form->button()->setText('Save changes');

$value = (new Field('HTML content', new TEXTAREA))
    ->addTip('The raw HTML value that will be inserted by this fragment')
    ->setRequired(true)
    ->setDefault($fragment->value())
    ->addForm($form);

$searchable = (new CheckboxField('Searchable'))
    ->addTip('Set whether this fragment can be found in the fragment insertion search tool in editor toolbars')
    ->setDefault($fragment->searchable())
    ->addForm($form);

if ($form->ready()) {
    SharedFragments::set($fragment->category(), $fragment->name(), $value->value(), $searchable->value());
    Notifications::flashConfirmation('Changes saved');
    throw new RedirectException(new URL('./'));
}

echo $form;