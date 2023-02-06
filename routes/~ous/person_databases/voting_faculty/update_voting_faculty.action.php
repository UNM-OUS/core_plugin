<h1>Update voting faculty list</h1>
<p>
    Expected to be in more or less the format we traditionally get from faculty contracts.
    The order of columns is not important, and extra columns the tool doesn't use will be ignored, but the following columns are required:
</p>
<ul>
    <li>Full Name</li>
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
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
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

$lastNameFirst = (new CheckboxField('Last names first'))
    ->setDefault(true)
    ->addForm($form);

$file = (new Field('Voting faculty spreadsheet', $upload = new UploadSingle()))
    ->setRequired(true)
    ->addForm($form);

if ($form->ready()) {
    $f = $upload->value();
    $lastNameFirst = $lastNameFirst->value();
    // empty existing values
    SharedDB::query()
        ->deleteFrom('voting_faculty')
        ->where('1 = 1')
        ->execute();
    // begin spreadsheet job
    $job = new SpreadsheetJob(
        $f['tmp_name'],
        function (array $row) use ($lastNameFirst) {
            // process things that need string fixing
            $college = StringFixer::organization($row['org level 3 desc']);
            $department = StringFixer::department($row['org desc']);
            $title = StringFixer::jobTitle($row['job title']);
            $netID = strtolower($row['netid']);
            $email = strtolower($row['email']);
            // load name, allowing overrides from PersonInfo
            $name = $row['full name'] ?? $row['name'];
            if ($lastNameFirst) {
                $name = preg_replace('/^(.+?), (.+)$/', '$2 $1', $name);
            }
            $name = preg_split('/ +/', $name);
            $lastName = array_pop($name);
            $lastName = PersonInfo::getLastNameFor($netID) ? PersonInfo::getLastNameFor($netID) : $lastName;
            $firstName = PersonInfo::getFirstNameFor($netID) ? PersonInfo::getFirstNameFor($netID) : implode(' ', $name);
            // update voting_faculty
            SharedDB::query()
                ->insertInto(
                    'voting_faculty',
                    [
                        'netid' => $netID,
                        'email' => $email,
                        'firstname' => $firstName,
                        'lastname' => $lastName,
                        'org' => $college,
                        'department' => $department,
                        'title' => $title,
                    ]
                )->execute();
            // update personinfo
            if (in_array(PersonInfo::getFor($netID, 'affiliation.type'), ['Upper administration', 'Regent'])) {
                // this person is or has been important, don't update their personinfo
            } elseif (trim(PersonInfo::getFullNameFor($netID))) {
                // this person is in the system, do a lighter update
                PersonInfo::setFor(
                    $netID,
                    [
                        'affiliation' => [
                            'type' => 'Faculty',
                            'org' => $college,
                            'department' => $department,
                            'title' => $title,
                        ],
                        'faculty' => [
                            'voting' => Semesters::current()->intVal(),
                            'semester' => Semesters::current()->intVal(),
                        ]
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
                        'fullname' => trim(preg_replace('/^(.+?), (.+)$/', '$2 $1', $row['full name'])),
                        'affiliation' => [
                            'type' => 'Faculty',
                            'org' => $college,
                            'department' => $department,
                            'title' => $title,
                        ],
                        'faculty' => [
                            'voting' => Semesters::current()->intVal(),
                            'semester' => Semesters::current()->intVal(),
                        ]
                    ]
                );
            }
            // return status
            return "Updated voting faculty: $firstName $lastName";
        },
        null,
        null,
        function () {
            // empty out existing values in voting_faculty table
            SharedDB::query()->getPdo()->exec("DELETE FROM voting_faculty");
            return "Truncated voting_faculty table";
        }
    );
    // redirect to job
    throw new RedirectException(new URL('?job=' . $job->group()));
}

echo $form;
echo '</div>';
