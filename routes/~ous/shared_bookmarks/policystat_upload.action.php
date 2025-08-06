<h1>PolicyStat upload</h1>
<p>
    This tool allows us to bulk update UAP and RPM using a CSV exported by PolicyStat from <a
            href="https://unmpolicy.policystat.com/search/?sort=category">the policy office's full policy list</a>.
    Running this tool will overwrite existing data for any UAP and RPM policies that are already in the database.
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
            $category = strtolower($row['applicability']);
            $title = $row['title'];
            $id = $row['policystat id'];
            $name = null;
            $url = $row['url'];
            if ($category == 'rpm') {
                $lower_title = strtolower($title);
                if (preg_match('/RPM ([0-9]+(\.[0-9]+)+)/im', $title, $matches)) {
                    $name = $matches[1];
                } elseif (str_contains($lower_title, 'foreword')) {
                    $name = 'foreword';
                } elseif (str_contains($lower_title, 'maintenance')) {
                    $name = 'maintenance';
                } elseif (str_contains($lower_title, 'preface')) {
                    $name = 'preface';
                }
            } elseif ($category == 'uap') {
                if (preg_match('/UAP ([0-9]+)/im', $title, $matches)) {
                    $name = $matches[1];
                }
            }
            // save by policystat id, searchable if name is empty
            SharedBookmarks::set(
                $category,
                $id,
                $title,
                $url,
                !$name
            );
            // if there's a name, save by it, always searchable
            if ($name) {
                SharedBookmarks::set(
                    $category,
                    $name,
                    $title,
                    $url,
                    true
                );
            }
            return sprintf(
                'Imported %s/%s',
                $category,
                $name ?: $id
            );
        }
    );
    throw new RedirectException(new URL('?job=' . $job->group()));
}

echo $form;
