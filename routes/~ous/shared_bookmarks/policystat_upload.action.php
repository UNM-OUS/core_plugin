<h1>PolicyStat upload</h1>
<p>
    This tool allows us to bulk update UAP and RPM using a CSV exported by PolicyStat from <a href="https://unmpolicy.policystat.com/search/?sort=category">the policy office's full policy list</a>. Running this tool will overwrite existing data for any UAP and RPM policies that are already in the database.
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
            /** @var string the actual displayed text for the bookmark */
            $title = $row['title'];
            $id = $row['policystat id'];
            /** @var string|null the slug to use as the bookmark "name" */
            $name = null;
            $url = $row['url'];
            // for RPM
            if ($category == 'rpm') {
                // check for special titles
                $lower_title = strtolower($title);
                // numbered policies
                if (preg_match('/^RPM ([0-9]+(\.[0-9]+)+)/im', $title, $matches)) {
                    // policy name is just the number
                    $name = $matches[1];
                    // add "Policy" after "RPM" at the beginning of the title
                    $title = preg_replace('/^(RPM) ([0-9]+(\.[0-9]+)+)/i', '$1 Policy $2', $title);
                }
                // special cases
                elseif (str_contains($lower_title, 'foreword')) {
                    $name = 'foreword';
                }
                elseif (str_contains($lower_title, 'maintenance')) {
                    $name = 'maintenance';
                }
                elseif (str_contains($lower_title, 'preface')) {
                    $name = 'preface';
                }
            }
            // for UAP 
            elseif ($category == 'uap') {
                // only bookmark if it's a numbered policy
                if (preg_match('/^UAP ([0-9]+)/im', $title, $matches)) {
                    // policy name is just the number
                    $name = $matches[1];
                    // add "Policy" after "UAP" at the beginning of the title
                    $title = preg_replace('/^(UAP) ([0-9]+)/i', '$1 Policy $2', $title);
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
                    true,
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
