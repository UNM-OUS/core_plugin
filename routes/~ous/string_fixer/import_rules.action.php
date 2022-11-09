<h1>Import string fixer rules</h1>
<p>
    The columns expected are: <em>Category</em>, <em>Input</em>, and <em>Output</em>.
</p>
<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\Cron\SpreadsheetJob;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

echo '<div class="navigation-frame" id="update-list-interface" data-target="_frame">';

// display progress bar if job is specified
if ($job = Context::arg('job')) {
    echo (new DeferredProgressBar($job));
    echo '</div>';
    return;
}

$form = new FormWrapper();

$file = (new Field('String fixer rules spreadsheet', $upload = new UploadSingle()))
    ->setRequired(true)
    ->addForm($form);

if ($form->ready()) {
    $f = $upload->value();
    // empty existing values
    SharedDB::query()
        ->deleteFrom('staff')
        ->where('1 = 1')
        ->execute();
    // begin spreadsheet job
    $job = new SpreadsheetJob(
        $f['tmp_name'],
        function (array $row) {
            $category = $row['category'];
            $input = $row['input'];
            $output = $row['output'];
            // update rule
            $exists = !!SharedDB::query()->from('stringfix')
                ->where('category', $category)
                ->where('input', $input)
                ->count();
            if ($exists) {
                SharedDB::query()->update('stringfix', ['output' => $output, 'needs_review' => false])
                    ->where('category', $category)
                    ->where('input', $input)
                    ->execute();
            } else {
                SharedDB::query()->insertInto(
                    'stringfix',
                    [
                        'category' => $category,
                        'input' => $input,
                        'output' => $output,
                        'needs_review' => false,
                    ]
                )->execute();
            }
            // return status
            return "Set string fix: $category: $input => $output";
        }
    );
    // redirect to job
    throw new RedirectException(new URL('?job=' . $job->group()));
}

echo $form;
echo '</div>';
