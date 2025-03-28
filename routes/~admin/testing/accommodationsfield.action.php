<h1>Test accommodations field</h1>
<?php

use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\AccommodationsField;

$form = new FormWrapper();
$field = new AccommodationsField('Test field');
$form->addChild($field);
echo $form;