<h1>Edit site-wide banner</h1>
<?php
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\UI\ButtonMenus\ButtonMenu;
use DigraphCMS\UI\ButtonMenus\ButtonMenuButton;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB\SiteAlerts;

$alert = SiteAlerts::get(Context::url()->actionSuffix());
if (!$alert) throw new HttpError(404, 'Alert not found');

$form = new FormWrapper();

$title = (new Field('Banner title'))
    ->setRequired(true)
    ->setDefault($alert->title())
    ->addForm($form);

$content = (new RichContentField("", Context::url()->actionSuffix()))
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

// tools for deleting alert

echo '<div class="card navigation-frame navigation-frame--stateless" id="alert-deletion-interface" data-target="_top">';
echo "<h2>Delete</h2>";
echo "<p>Delete this alert. This action cannot be undone. Generally if an alert has been displayed it is better to set and end date instead so that we have a record of what was displayed to users when.</p>";

if (Context::arg('delete') != 1) {
    printf("<a href='%s' class='button button--warning' data-target='_frame'>Delete alert</a>", new URL('?delete=1'));
} else {
    $buttons = new ButtonMenu();
    $buttons->setTarget('_top');
    $buttons->addButton(new ButtonMenuButton('Yes, delete this alert', function () use ($alert) {
        $alert->delete();
        Notifications::flashConfirmation('Alert deleted');
        throw new RedirectException(new URL('./'));
    }, ['button--danger']));
    $buttons->addButton(new ButtonMenuButton('Cancel deletion', function () {
        throw new RedirectException(new URL('?delete=0'));
    }));
    echo "<p>Are you sure? This action cannot be undone.</p>";
    echo $buttons;
}

echo "</div>";