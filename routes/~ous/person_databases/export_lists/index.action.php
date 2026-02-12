<h1>Export faculty/staff lists</h1>
<p>
    This tool exports faculty and staff lists, optionally filtered by school/college and department name, in various useful formats.
</p>
<?php

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteField;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteInput;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

echo '<div class="navigation-frame navigation-frame--stateless" id="list-exporter-picker-interface">';
$form = new FormWrapper();
$form->button()->setText('Continue');
$form->setData('target', 'list-exporter-picker-interface');

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
    $url = new URL('_list_export.html');
    $url->arg('type', $type->value());
    if ($org->value()) $url->arg('org', $org->value());
    if ($department && $department->value()) $url->arg('department', $department->value());
    printf('<div id="list-export-interface" class="navigation-frame navigation-frame--stateless" data-target="_frame" data-initial-source="%s"></div>', $url);
}

echo '</div>';
