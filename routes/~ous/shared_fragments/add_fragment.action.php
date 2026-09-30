<h1>Add shared fragment</h1>
<?php

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\TEXTAREA;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedFragments\SharedFragments;

Notifications::printWarningHTML("<strong>Warning:</strong> shared fragments create a shortcode tag that will apply to all OUS sites, and are by design difficult to delete.");

$form = new FormWrapper();
$form->button()->setText('Create fragment');

$category = (new Field('Category'))
    ->addTip('This field will be the main tag name of this fragment')
    ->setRequired(true)
    ->addForm($form);

$name = (new Field('Name'))
    ->addTip('This field will be the value after the optional equals sign of the tag to insert this value')
    ->addForm($form);

$value = (new Field('HTML content', input: new TEXTAREA()))
    ->addTip('The raw HTML value that will be inserted by this fragment')
    ->setRequired(true)
    ->addForm($form);

$searchable = (new CheckboxField('Searchable'))
    ->addTip('Set whether this fragment can be found in the fragment insertion search tool in editor toolbars')
    ->addForm($form);

if ($form->ready()) {
    SharedFragments::set(
        strtolower($category->value()),
        strtolower($name->value()),
        $value->value(),
        $searchable->value(),
    );
    Notifications::flashConfirmation('Changes saved');
    throw new RedirectException(new URL('./'));
}

echo $form;