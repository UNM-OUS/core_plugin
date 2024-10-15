<?php

use DigraphCMS\CodeMirror\YamlArrayInput;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Notifications;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;

$person = PersonInfo::fetch(Context::url()->actionSuffix());

$form = new FormWrapper();

$data = (new Field('User data', new YamlArrayInput()))
    ->setDefault($person->get())
    ->addForm($form);

if ($form->ready()) {
    $person->set(null, $data->value());
    $person->save();
    Notifications::flashConfirmation('PersonInfo record updated');
    throw new RefreshException();
}

echo $form;
