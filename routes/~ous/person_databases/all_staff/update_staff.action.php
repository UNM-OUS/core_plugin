<h1>Update staff list</h1>
<p>
    Expected to be in more or less the format we traditionally get from HR.
    The order of columns is not important, and extra columns the tool doesn't use will be ignored, but the following columns are required:
</p>
<ul>
    <li>Name</li>
    <li>Email</li>
    <li>Netid</li>
    <li>Org Level 3 Desc</li>
    <li>Org Desc</li>
    <li>Job Title</li>
</ul>
<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\Cron\SpreadsheetJob;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use DigraphCMS_Plugins\unmous\ous_digraph_module\StringFixer;

echo '<div class="navigation-frame" id="update-list-interface" data-target="_frame">';

// display progress bar if job is specified
if ($job = Context::arg('job')) {
    echo (new DeferredProgressBar($job));
    echo '</div>';
    return;
}

$form = new FormWrapper();

$file = (new Field('Staff list spreadsheet', $upload = new UploadSingle()))
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
            // process things that need string fixing
            $organization = StringFixer::organization($row['org level 3 desc']);
            $department = StringFixer::department($row['org desc']);
            $title = StringFixer::jobTitle($row['job title']);
            $netID = strtolower($row['netid']);
            $email = strtolower($row['email']);
            // load name, allowing overrides from PersonInfo
            $name = preg_split('/ +/', $row['name']);
            $lastName = array_pop($name);
            $lastName = PersonInfo::getLastNameFor($netID) ?? $lastName;
            $firstName = PersonInfo::getFirstNameFor($netID) ?? implode(' ', $name);
            // update staff
            SharedDB::query()
                ->insertInto(
                    'staff',
                    [
                        'netid' => $netID,
                        'email' => $email,
                        'firstname' => $firstName,
                        'lastname' => $lastName,
                        'org' => $organization,
                        'department' => $department,
                        'title' => $title,
                    ]
                )->execute();
            // update personinfo
            if (in_array(PersonInfo::getFor($netID, 'affiliation.type'), ['Upper administration', 'Regent'])) {
                // this person is or has been important, don't update their personinfo
            } elseif (PersonInfo::getFirstNameFor($netID) && PersonInfo::getLastNameFor($netID)) {
                // this person is in the system, do a lighter update to just their affiliation
                PersonInfo::setFor(
                    $netID,
                    [
                        'affiliation' => [
                            'type' => PersonInfo::getFor($netID, 'affiliation.type') != 'Staff'
                                ? 'Staff'
                                : PersonInfo::getFor($netID, 'affiliation.type'),
                            'org' => $organization,
                            'department' => $department,
                            'title' => $title,
                        ],
                        'staff' => Semesters::current()->intVal()
                    ]
                );
            } else {
                // this is a new person, update everything
                PersonInfo::setFor(
                    $netID,
                    [
                        'email' => $email,
                        'firstname' => $firstName,
                        'lastname' => $lastName,
                        'fullname' => trim("$firstName $lastName"),
                        'affiliation' => [
                            'type' => 'Staff',
                            'org' => $organization,
                            'department' => $department,
                            'title' => $title,
                        ],
                        'staff' => Semesters::current()->intVal()
                    ]
                );
            }
            // return status
            return "Updated staff: $firstName $lastName";
        }
    );
    // redirect to job
    throw new RedirectException(new URL('?job=' . $job->group()));
}

echo $form;
echo '</div>';
