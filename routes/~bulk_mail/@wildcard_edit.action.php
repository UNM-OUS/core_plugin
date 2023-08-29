<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Email;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing || $mailing->sent()) throw new HttpError(404);

printf('<h1>Edit: %s</h1>', $mailing->name());
Breadcrumb::setTopName($mailing->name());

$form = new FormWrapper();
$form->button()->setText('Save draft');

$name = (new Field('Name'))
    ->setDefault($mailing->name())
    ->setRequired(true)
    ->addForm($form);

$from = (new Field('From address', new Email()))
    ->setDefault($mailing->from())
    ->setRequired(true)
    ->addForm($form);

$category = (new Field('Category', new SELECT(BulkMail::categories())))
    ->setDefault($mailing->category())
    ->setRequired(true)
    ->addForm($form);

$subject = (new Field('Subject line'))
    ->setDefault($mailing->subject())
    ->setRequired(true)
    ->addForm($form);

$body = (new RichContentField('Body content', 'bulk_mail_body'))
    ->setDefault($mailing->body())
    ->setRequired(true)
    ->addForm($form);

if ($form->ready()) {
    DB::query()->update(
        'bulk_mail',
        [
            'name' => $name->value(),
            '`from`' => $from->value(),
            'category' => $category->value(),
            'subject' => $subject->value(),
            'body' => $body->value()->source(),
            'updated' => time(),
            'updated_by' => Session::uuid()
        ],
        $mailing->id()
    )->execute();
    throw new RefreshException();
}

echo $form;
