<h1>Begin a blank bulk mailing</h1>
<?php

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Email;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;

$form = new FormWrapper();
$form->button()->setText('Create draft');

$name = (new Field('Name'))
    ->setRequired(true)
    ->addForm($form);

$from = (new Field('From address', new Email))
    ->setDefault(Config::get('bulk_mail.default_from'))
    ->setRequired(true)
    ->addForm($form);

$subject = (new Field('Subject line'))
    ->setRequired(true)
    ->addForm($form);

$category = (new Field('Category', new SELECT(BulkMail::categories())))
    ->setRequired(true)
    ->addForm($form);

if ($form->ready()) {
    $id = DB::query()
        ->insertInto(
            'bulk_mail',
            [
                'name' => $name->value(),
                '`from`' => $from->value(),
                'subject' => $subject->value(),
                'body' => '',
                'sources' => '',
                'extra_recipients' => '',
                'category' => $category->value(),
                'created' => time(),
                'created_by' => Session::uuid(),
                'updated' => time(),
                'updated_by' => Session::uuid()
            ]
        )
        ->execute();
    throw new RedirectException(new URL("edit:$id"));
}

echo $form;
