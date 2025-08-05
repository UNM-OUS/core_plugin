<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\TEXTAREA;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\CustomLists\CustomLists;

$list = CustomLists::get(Context::url()->actionSuffix());
if (!$list) throw new HttpError(404);

printf('<h1>Edit list: %s</h1>', $list->label());

$form = new FormWrapper();
$form->button()->setText('Save');

$emails = (new Field('Email addresses', new TEXTAREA()))
    ->addTip('Enter one email address per line')
    ->addTip('Emails will all be converted to lower case and duplicates will be removed')
    ->addTip('Invalid email addresses will be discarded')
    ->setRequired(false)
    ->setDefault(implode(PHP_EOL, $list->emails()))
    ->addForm($form);

if ($form->ready()) {
    $addresses = preg_split('/\r?\n/', $emails->value());
    assert(is_array($addresses));
    $list->replaceRecipients($addresses);
    $list->update();
    Notifications::flashConfirmation('List updated');
    throw new RedirectException(new URL('./'));
}

echo $form;

// deletion tool
if (!Permissions::inMetaGroup('bulkmail__edit')) return;

echo '<div class="card navigation-frame navigation-frame--stateless" id="list-deletion-interface" data-target="_top">';
echo "<h2>Delete</h2>";
echo "<p>Delete this custom list. This action cannot be undone.</p>";

if (Context::arg('delete') != 1) {
    printf("<a href='%s' class='button button--warning' data-target='_frame'>Delete list</a>", new URL('?delete=1'));
} else {
    $confirm = new CallbackLink(function () use ($list) {
        $list->delete();
        Notifications::flashConfirmation(sprintf('Custom recipient list deleted: %s', $list->label()));
        throw new RedirectException(new URL('./'));
    });
    $cancel = new CallbackLink(function () {
        throw new RedirectException(new URL('?delete=0'));
    });
    $confirm->addChild('Yes, delete this list')
        ->addClass('button button--danger');
    $cancel->addChild('Cancel deletion')
        ->addClass('button button--neutral');
    printf('<p>Are you sure? This action cannot be undone.</p><p>%s%s</p>', $confirm, $cancel);
}

echo "</div>";