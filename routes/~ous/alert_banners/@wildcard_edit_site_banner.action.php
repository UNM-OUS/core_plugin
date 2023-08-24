<h1>Add site-wide banner</h1>
<?php
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB\SiteAlerts;

$alert = SiteAlerts::get(Context::arg('uuid'));
if (!$alert) throw new HttpError(404, 'Alert not found');

$form = new FormWrapper();

$title = (new Field('Banner title'))
    ->setRequired(true)
    ->setDefault($alert->title())
    ->addForm($form);

$content = (new RichContentField("", Context::arg('uuid')))
    ->setRequired(true)
    ->setDefault($alert->contentSource())
    ->addForm($form);

$class = (new Field("Display class", new SELECT([
'information' => 'Information/notice',
'warning' => 'Warning',
'safe' => 'Safe/confirmation',
'danger' => 'Danger',
'medical' => 'Medical information',
])))
    ->setRequired(true)
    ->setDefault($alert->class())
    ->addForm($form);

$start = (new DatetimeField('Start date/time'))
    ->setDefault($alert->start())
    ->addForm($form);

$end = (new DatetimeField('End date/time'))
    ->setDefault($alert->end())
    ->addForm($form);

if ($form->ready()) {
    $alert
        ->setTitle($title->value())
        ->setContent($content->value()->source())
        ->setClass($class->value())
        ->setStart($start->value())
        ->setEnd($end->value())
        ->update();
    Notifications::flashConfirmation('Alert updated');
    throw new RedirectException(new URL('site_banners.html'));
}

echo $form;