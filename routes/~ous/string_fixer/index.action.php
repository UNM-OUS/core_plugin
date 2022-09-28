<h1>OUS string fixer</h1>
<p>
    This is a general purpose tool for correcting common sources of ugly strings.
    Most of the corrections here are used to correct ugly degree abbreviations from Banner, or ugly org info from faculty/staff lists.
    New terms that need to have their output value confirmed are listed at the top.
</p>
<p>
    Note that these rules are generally applied at the time of import for performance reasons.
    This means that updating things here will not retroactively update things like degree or faculty/staff lists that have already been imported.
</p>
<?php

use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\HTML\Icon;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

$rules = SharedDB::query()->from('stringfix')
    ->order('needs_review desc')
    ->order('COALESCE(output,input)');

$table = new PaginatedTable(
    $rules,
    function (array $row) {
        $id = md5(serialize([$row['category'], $row['input']]));
        // set up form
        $form = new FormWrapper($id);
        $form->addClass('inline-form');
        $form->setData('target', '_frame');
        $input = new INPUT($id);
        $input->setDefault($row['output']);
        $form->addChild($input);
        // give button the right text

        // add callback
        $form->addCallback(function () use ($row, $input) {
            SharedDB::query()->update('stringfix', [
                'output' => $input->value(),
                'needs_review' => false,
            ])->where('category', $row['category'])
                ->where('input', $row['input'])
                ->execute();
            throw new RefreshException();
        });
        return [
            $row['needs_review'] ? new Icon('star') : '',
            $row['category'],
            $row['input'],
            $form
        ];
    },
    [
        '',
        new ColumnStringFilteringHeader('Category', 'category'),
        new ColumnStringFilteringHeader('Input', 'input'),
        new ColumnStringFilteringHeader('Output', 'output'),
    ]
);

echo $table;
