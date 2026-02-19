<h1>Add custom recipient list</h1>
<?php

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\TEXTAREA;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\CustomLists\CustomLists;

$form = new FormWrapper();
$form->button()->setText('Create list');

$name = (new Field('List name'))
        ->addTip('String to be displayed in the list selection interface')
        ->setRequired(true)
        ->addForm($form);

$emails = (new Field('List of emails', new TEXTAREA()))
        ->addTip('Enter one email address per line')
        ->addTip('Emails will all be converted to lower case and duplicates will be removed')
        ->addTip('Invalid email addresses will be discarded')
        ->setRequired(false)
        ->addForm($form);

if ($form->ready()) {
        $list = CustomLists::create(
                $name->value(),
        );
        $addresses = preg_split('/\r?\n/', $emails->value());
        assert(is_array($addresses));
        $list->replaceRecipients($addresses);
        $list->update();
        Notifications::flashConfirmation('List created');
        throw new RedirectException(new URL('./'));
}

echo $form;
