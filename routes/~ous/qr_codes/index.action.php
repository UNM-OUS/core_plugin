<h1>QR code generator</h1>
<?php

use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UrlInput;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Permalinks\Permalinks;
use DigraphCMS_Plugins\unmous\ous_digraph_module\QrGenerator;

$form = new FormWrapper();

$url = (new Field('URL', new UrlInput()))
    ->setRequired(true)
    ->addForm($form);

$permalink = (new CheckboxField('Create permalink'))
    ->addTip(sprintf('If checked, the QR will point to a random URL like <kbd>%s</kbd>, which will redirect to the URL you enter.', new URL('/pl:' . strtolower(Digraph::uuid()))))
    ->addTip('Use this if you want to be able to change where the QR code points later, or if you would like to count uses of it.')
    ->addForm($form);

if ($form->ready()) {
    if ($permalink->value()) {
        $permalink = Permalinks::create($url->value());
        Notifications::flashConfirmation('Permalink created');
        throw new RedirectException(new URL('../permalinks/qr:' . $permalink->slug()));
    } else {
        Notifications::confirmation('QR code generated');
        printf('<p><strong>PNG version</strong><br><img src="%s"></p>', QrGenerator::pngFile($url->value())->url());
        printf('<p><a href="%s">SVG version</a></p>', QrGenerator::svgFile($url->value())->url());
    }
} else {
    echo $form;
}
