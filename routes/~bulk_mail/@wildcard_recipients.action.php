<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\TEXTAREA;
use DigraphCMS\HTML\Icon;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients\AbstractRecipientSource;

$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));
if (!$mailing || $mailing->sent()) throw new HttpError(404);
include __DIR__ . '/_actions.include.php';

printf('<h1>Select recipients: %s</h1>', $mailing->name());
Breadcrumb::setTopName($mailing->name());

if ($mailing->scheduled()) Notifications::printWarning("This message is scheduled to send. Its recipient list will be rebuilt automatically before sending.");

echo "<h2>Select recipient sources</h2>";

// dynamic recipient sources
echo "<div class='navigation-frame navigation-frame--stateless' id='recipient-sources'>";
$selectedSourceNames = $mailing->sourceNames();
// display selected sources
echo count($mailing->sources()) ? new PaginatedTable(
    $mailing->sources(),
    function (AbstractRecipientSource $source) use ($mailing) {
        return [
            '<span class="notification notification--confirmation">' . $source->name() . '</span>',
            '<span class="notification notification--confirmation">' . $source->label() . '</span>',
            '~' . number_format($source->count()),
            (new CallbackLink(function () use ($source, $mailing) {
                $sources = array_filter($mailing->sourceNames(), function ($s) use ($source) {
                    return !($s == $source->name() || str_starts_with($source->name(), "$s/"));
                });
                DB::query()->update(
                    'bulk_mail',
                    [
                        'sources' => implode(',', $sources),
                        'updated' => time(),
                        'updated_by' => Session::uuid()
                    ],
                    $mailing->id()
                )->execute();
            }))
                ->setData('target', 'recipient-sources')
                ->addChild(new Icon('delete', 'Remove source'))
        ];
    }
) : '';
// display non-selected sources
$table = new PaginatedTable(
    BulkMail::sources(),
    function (AbstractRecipientSource $source) use ($selectedSourceNames, $mailing): array {
        $selected = false;
        foreach ($selectedSourceNames as $s) {
            if ($s == $source->name() || str_starts_with($source->name(), "$s/")) {
                $selected = true;
            }
        }
        return [
            $source->name(),
            $source->label(),
            number_format($source->count()),
            $selected
                ? ''
                : (new CallbackLink(function () use ($source, $selectedSourceNames, $mailing) {
                    $selectedSourceNames = array_filter($selectedSourceNames, function ($s) use ($source) {
                        return !($s == $source->name() || str_starts_with($source->name(), "$s/"));
                    });
                    $selectedSourceNames[] = $source->name();
                    DB::query()->update(
                        'bulk_mail',
                        [
                            'sources' => implode(',', $selectedSourceNames),
                            'updated' => time(),
                            'updated_by' => Session::uuid()
                        ],
                        $mailing->id()
                    )->execute();
                }))
                ->setData('target', 'recipient-sources')
                ->addChild(new Icon('add', 'Add source'))
        ];
    }
);
$table->paginator()->perPage(1000);
echo $table;
echo "</div>";

// extra recipients list
echo "<h3>Extra email recipients</h3>";
echo "<div class='navigation-frame navigation-frame--stateless' id='extra-addresses'>";
$form = new FormWrapper();
$form->setData('target', '_frame');
$form->button()->setText('Save extra recipient list');
$extra = (new Field('Emails (one per line)', new TEXTAREA))
    ->addTip('Duplicates will be automatically removed, including duplicates that are already included in the sources above.')
    ->addTip('Lines beginning with <kbd>#</kbd> are ignored, and can be used as comments.')
    ->addTip('Blank lines are ignored.')
    ->setDefault($mailing->extraRecipients())
    ->addForm($form);
if ($form->ready()) {
    DB::query()->update(
        'bulk_mail',
        [
            'extra_recipients' => $extra->value() ?? '',
            'updated' => time(),
            'updated_by' => Session::uuid()
        ],
        $mailing->id()
    )->execute();
    throw new RefreshException();
}
echo $form;
echo "</div>";
