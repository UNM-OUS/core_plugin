<h1>Add global banner</h1>
<?php
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB\GlobalAlert;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB\SiteAlert;

Context::ensureUUIDArg();
$form = new FormWrapper();

$title = (new Field('Banner title'))
    ->setRequired(true)
    ->addForm($form);

$content = (new RichContentField("", Context::arg('uuid')))
    ->setRequired(true)
    ->addForm($form);

$class = (new Field("Display class", new SELECT([
'information' => 'Information/notice',
'warning' => 'Warning',
'safe' => 'Safe/confirmation',
'danger' => 'Danger',
'medical' => 'Medical information',
])))
    ->setRequired(true)
    ->addForm($form);

$start = (new DatetimeField('Start date/time'))
    ->addForm($form);

$end = (new DatetimeField('End date/time'))
    ->addForm($form);

if ($form->ready()) {
    $alert = new GlobalAlert(
        $title->value(),
        $content->value()->source(),
        $class->value(),
        Context::arg('uuid'),
        $start->value(),
        $end->value(),
    );
    $alert->create();
    Notifications::flashConfirmation('Alert created');
    throw new RedirectException(new URL('site_banners.html'));
}

echo $form;