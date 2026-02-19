<h1>Custom recipient lists</h1>
<p>
    Use this system to add and modify your own custom recipient lists, which can be used in bulk mailings. The advantage
    to using these instead of the "extra recipients" field for a mailing is that these can be updated for multiple
    mailings at once. So if you want to schedule many mailings at once, but who they go to might change, you can use
    these lists to update all future scheduled mailings at once. A good example of when this is useful is the FYFD
    student lists, which we update periodically throughout the summer, manually, from lists we get from outside our
    office.
</p>
<?php

use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\CustomLists\CustomLists;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\CustomLists\CustomRecipientList;

$lists = CustomLists::allLists();
echo new PaginatedTable(
    $lists,
    function (CustomRecipientList $list): array {
        $edit_url = new DigraphCMS\URL\URL('edit:' . $list->uuid());
        return [
            sprintf('<a href="%s">%s</a>', $edit_url, $list->label()),
            $list->count(),
            Format::date($list->created()),
            Format::date($list->updated()),
        ];
    },
    [
        'Name',
        'Emails',
        'Created',
        'Updated',
    ],
);
