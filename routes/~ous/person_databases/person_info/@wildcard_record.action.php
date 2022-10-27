<?php

use DigraphCMS\CodeMirror\YamlArrayInput;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;

$person = PersonInfo::fetch(Context::url()->actionSuffix());

$form = new FormWrapper();

$data = (new Field('User data', new YamlArrayInput()))
    ->setDefault($person->get())
    ->addForm($form);

echo $form;
