<h1>Update faculty list</h1>
<p>
    Uploaded files are expected to be in more or less the formats we traditionally get from HR and Faculty Contracts. The order of columns is not important, and extra columns the tool doesn't use will be ignored, but the following columns are expected:
</p>
<ul>
    <li>Name, Full Name, or First Name and Last Name</li>
    <li>Preferred name (optional)</li>
    <li>Email</li>
    <li>UNM ID or Banner ID</li>
    <li>NetID (optional if only updating voting status)</li>
    <li>Org Level 3 Desc</li>
    <li>Org Desc</li>
    <li>Job Title</li>
</ul>
<p>
    The column "Academic Title" is not required, but is recommended to get the best possible rank information, especially for faculty with chair/dean-type titles that are not their academic titles.
    For updates that do not include an academic title column, existing rank data will be used where available if necessary.
</p>
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
        'all'                => 'All faculty',
        'voting'             => 'Voting faculty',
        'voting_status_only' => 'Voting status only',
    ],
    '-- select --',
)))
    ->addTip("If \"All faculty\" is selected, all faculty records will be updated. Any faculty records not included in the uploaded spreadsheet will be deleted.")
    ->addTip("If \"Voting faculty\" is selected, only voting faculty records will be updated. Any voting faculty records not included in the uploaded spreadsheet will be deleted, but non-voting records will not be touched.")
    ->addTip("If \"Voting status only\" is selected, only the voting status of existing faculty records will be updated. No records will be deleted, but no new records will be added either. Matches will be made by either Banner ID or NetID, so one or both of those columns must be included in the spreadsheet for this option to work.")
    ->setRequired(true)
    ->addForm($form);

$orgs = SharedDB::query()
    ->from('faculty_list')
    ->select('DISTINCT(org) as org', true)
    ->orderBy('org')
    ->fetchAll();
if (!is_array($orgs))
    $orgs = [];
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
    assert($type == 'all' || $type == 'voting' || $type == 'voting_status_only');
    $org = $org->value();
    assert(is_string($org) && $org || is_null($org));
    $job_group = Digraph::uuid('update_faculty');
    $job = new SpreadsheetJob(
        $file->value()['tmp_name'],
        function (array $row, DeferredJob $job) use ($type) {
            switch ($type) {
                case 'all':
                    FacultyInfo::import($row, null, $job->group());
                    break;
                case 'voting':
                    FacultyInfo::import($row, true, $job->group());
                    break;
                case 'voting_status_only':
                    FacultyInfo::import($row, true, $job->group(), true);
                    break;
            }
            return "Imported faculty record";
        },
        teardownFn: function () use ($type, $org, $job_group) {
            // if voting_status_only or voting, teardown should only set voting status to false for old records, but not delete anything
            if ($type == 'voting_status_only' || $type == 'voting') {
                $query = SharedDB::query()
                    ->update('faculty_list')
                    ->set([
                        'voting' => 0,
                        'job'    => $job_group,
                    ])
                    ->where('job <> ?', $job_group);
            }
            // otherwise fully clear out old records that were not updated by this job
            else {
                $query = SharedDB::query()
                    ->delete('faculty_list')
                    ->where('job <> ?', $job_group);
            }
            // if org is set, only touch records from that org
            if ($org) {
                $query->where('org = ?', $org);
            }
            // execute
            $count = $query->execute();
            assert(is_int($count));
            if ($org)
                return "Teardown cleaned up $count '$type' records from $org";
            else
                return "Teardown cleaned up $count '$type' records";
        },
        group: $job_group,
    );
    // redirect to job progress
    throw new RedirectException(new URL('?job=' . $job->group()));
}

echo $form;
