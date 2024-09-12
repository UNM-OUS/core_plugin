<h1>Update faculty list</h1>
<p>
    Uploaded files are expected to be in more or less the formats we traditionally get from HR and Faculty Contracts.
    The order of columns is not important, and extra columns the tool doesn't use will be ignored, but the following columns are required:
</p>
<ul>
    <li>Name, Full Name, or First Name and Last Name</li>
    <li>Email</li>
    <li>NetID</li>
    <li>Org Level 3 Desc</li>
    <li>Org Desc</li>
    <li>Job Title</li>
    <li>Academic Title</li>
</ul>
<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\Cron\SpreadsheetJob;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\People\FacultyInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

// display progress bar if job is specified
if ($job = Context::arg('job')) {
    echo (new DeferredProgressBar($job));
    return;
}

$form = new FormWrapper();
$form->button()->setText('Upload file');

$type = (new Field('List type', new SELECT(
    [
        'all' => 'All faculty',
        'voting' => 'Voting faculty',
    ],
    '-- select --'
)))
    ->setRequired(true)
    ->addForm($form);

$orgs = SharedDB::query()
    ->from('faculty_list')
    ->select('DISTINCT(org) as org', true)
    ->orderBy('org')
    ->fetchAll();
if (!is_array($orgs)) $orgs = [];
$orgs = array_map(fn($o) => $o['org'], $orgs);
$org = (new Field('School/college', new SELECT(array_combine($orgs, $orgs), '-- all --')))
    ->addTip('If you are not sure, leave this blank')
    ->addTip('If you are uploading a spreadsheet that only updates a single school/college, select it here and no records from other schools/colleges will be modified')
    ->setRequired(false)
    ->addForm($form);

$file = (new Field('Faculty list spreadsheet', $upload = new UploadSingle()))
    ->setRequired(true)
    ->addForm($form);

if ($form->ready()) {
    $type = $type->value();
    assert($type == 'normal' || $type == 'voting');
    $org = $org->value();
    assert(is_string($org) && $org || is_null($org));
    $job_group = Digraph::uuid('update_faculty');
    $job = new SpreadsheetJob(
        $file->value()['tmp_name'],
        function (array $row, DeferredJob $job) use ($type) {
            $voting = null;
            if ($type == 'voting') $voting = true;
            FacultyInfo::import($row, $voting, $job->group());
            return "Imported faculty record for " . $row['netid'];
        },
        teardownFn: function () use ($type, $org, $job_group) {
            // teardown function should clear all faculty records of different
            // job IDs that would have been in this update
            $query = SharedDB::query()
                ->delete('faculty_list')
                ->where('job <> ?', $job_group);
            // if type is voting, only delete voting faculty
            if ($type == 'voting') {
                $query->where('voting');
            }
            // if org is set, only delete faculty from that org
            if ($org) {
                $query->where('org', $org);
            }
            // execute
            $count = $query->execute();
            if ($org) return "Teardown deleted $count '$type' records from $org";
            else return "Teardown deleted $count '$type' records";
        },
        group: $job_group
    );
    // redirect to job progress
    throw new RedirectException(new URL('?job=' . $job->group()));
}

echo $form;
