<h1>Export faculty/staff for Opinio invites</h1>
<p>
    This tool exports faculty or staff lists, optionally filtered by school/college and department name, in the format
    that Opino likes.
    The files exported here are not actually standard CSV files, because Opinio is actually very picky about its CSV
    file format.
</p>
<?php

use DigraphCMS\FS;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteField;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteInput;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OpinioExporter;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use Envms\FluentPDO\Queries\Select as QueriesSelect;

echo '<div class="navigation-frame navigation-frame--stateless" id="opinio-export-interface">';
$form = new FormWrapper();
$form->button()->setText('Continue');
$form->setData('target', 'opinio-export-interface');

$type = (new Field('Affiliation', new SELECT([
    'voting_faculty' => 'Voting faculty',
    'all_faculty' => 'All faculty',
    'staff' => 'Staff',
])))
    ->addForm($form);

$query = SharedDB::query();
switch ($type->value()) {
    case 'voting_faculty':
        $query = $query->from('faculty_list')
            ->where('voting');
        break;
    case 'all_faculty':
        $query = $query->from('faculty_list');
        break;
    case 'staff':
        $query = $query->from('staff_list');
        break;
}
assert($query instanceof QueriesSelect);

if ($type->value()) {
    $org = (
        new AutocompleteField(
            'School/College/Organization',
            (
                new AutocompleteInput(
                    null,
                    new URL('/~api/v1/unm-affiliation/org.php'),
                    function ($value) use ($query) {
                        if (!$value) return null;
                        if ($value != 'Other' && !Permissions::inMetaGroup('unmaffiliation__edit')) {
                            $query = clone $query;
                            $query->where('org', $value);
                            if (!$query->count()) return null;
                        }
                        return [
                            'html' => $value,
                            'value' => $value
                        ];
                    }
                )
            )->addClass('autocomplete-input--autopopulate')
        )
    )
        ->addForm($form);
} else $org = null;

if (isset($org) && $org->value() && !in_array($org->value(), ['Other'])) {
    $department = (
        new AutocompleteField(
            'Department',
            (
                new AutocompleteInput(
                    null,
                    new URL('/~api/v1/unm-affiliation/department.php?org=' . $org->value()),
                    function ($value) use ($query, $org) {
                        if (!$value) return null;
                        if (!Permissions::inMetaGroup('unmaffiliation__edit')) {
                            $query = clone $query;
                            $query->where('org', $org->value())
                                ->where('department', $value);
                            if (!$query->count()) return null;
                        }
                        return [
                            'html' => $value,
                            'value' => $value
                        ];
                    }
                )
            )->addClass('autocomplete-input--autopopulate')
        )
    )
        ->addForm($form);
} else $department = null;

echo $form;

if ($type->value()) {
    $file = new DeferredFile(
        sprintf(
            'Opinio invites - %s - %s.csv',
            implode(
                ' - ',
                array_filter(
                    [
                        $type->value(),
                        $org->value(),
                        $department?->value(),
                    ],
                    fn($e) => !empty($e)
                )
            ),
            date('YmdGi')
        ),
        function (DeferredFile $file) use ($query, $org, $department): void {
            $query = clone $query;
            $query->select('CONCAT(first_name," ",last_name) as Name', true);
            $query->select('email as Email');
            $query->select('netid as NetID');
            $query->select('org, department, title');
            if ($org->value()) $query->where('org', $org->value());
            if ($department?->value()) $query->where('department', $department->value());
            $results = $query->fetchAll();
            assert(is_array($results));
            FS::touch($file->path());
            file_put_contents(
                $file->path(),
                OpinioExporter::array($results, true)
            );
        },
        [
            'opinio export',
            $type->value(),
            $org->value(),
            $department?->value(),
        ]
    );

    echo '<h2>Download current selections</h2>';
    printf(
        '<p><a href="%s" class="button button--inverted" target="_blank">%s</a></p>',
        $file->url(),
        $file->filename(),
    );
}

echo '</div>';
