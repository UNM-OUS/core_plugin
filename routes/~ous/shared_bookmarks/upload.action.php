<h1>Upload shared bookmarks</h1>
<p>
    Expects a spreadsheet in the format provided by the download link on the shared bookmarks list page.
    The spreadsheet should have columns for category, name, title, and URL.
    This tool will replace existing bookmarks if they are in the file, but does not delete bookmarks that are not included.
</p>
<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\Cron\SpreadsheetJob;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedBookmarks\SharedBookmarks;

// display status
if ($job = Context::arg('job')) {
    Breadcrumb::parent(new URL('?'));
    Breadcrumb::setTopName('Processing upload');
    echo (new DeferredProgressBar($job))
        ->setDisplayAfter('Bookmarks imported')
        ->setBounceAfter(new URL('?'));
    return;
}

// upload form
$form = new FormWrapper();
$form->button()->setText('Upload');
(new Field('Upload spreadsheet', $upload = new UploadSingle()))
    ->setRequired(true)
    ->addForm($form);

// handle form submission
if ($form->ready()) {
    // spawn deferred job and redirect to status display
    $job = new SpreadsheetJob(
        $upload->value()['tmp_name'],
        function (array $row) {
            SharedBookmarks::set(
                $row['category'],
                $row['name'] ?: "",
                $row['title'],
                $row['url']
            );
            return sprintf(
                'Imported %s/%s',
                $row['category'],
                $row['name']
            );
        }
    );
    throw new RedirectException(new URL('?job=' . $job->group()));
}

echo $form;
