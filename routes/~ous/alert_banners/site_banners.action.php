<h1>Site-wide banners</h1>
<p>
    Banners created here will appear on every page on this site.
</p>
<?php
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB\SiteAlert;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB\SiteAlerts;

echo "<h2>Current</h2>";
echo new PaginatedTable(
    SiteAlerts::new()
        ->currentAlerts()
        ->order('end_time asc'),
    function (SiteAlert $alert): array {
        return [
            $alert->start() ? Format::datetime($alert->start()) : '',
            $alert->end() ? Format::datetime($alert->end()) : '',
            $alert->render(),
            sprintf('<a href="%s" class="button button--small button--inverted">edit</a>', $alert->editUrl()),
        ];
    },
    [
        'Start',
        'End',
        'Alert',
        'Edit'
    ]
);

echo "<h2>Upcoming</h2>";
echo new PaginatedTable(
    SiteAlerts::new()
        ->upcoming()
        ->order('start_time asc'),
    function (SiteAlert $alert): array {
        return [
            $alert->start() ? Format::datetime($alert->start()) : '',
            $alert->end() ? Format::datetime($alert->end()) : '',
            $alert->render(),
            sprintf('<a href="%s" class="button button--small button--inverted">edit</a>', $alert->editUrl()),
        ];
    },
    [
        'Start',
        'End',
        'Alert',
        'Edit'
    ]
);

echo "<h2>Expired</h2>";
echo new PaginatedTable(
    SiteAlerts::new()
        ->past()
        ->order('end_time desc'),
    function (SiteAlert $alert): array {
        return [
            $alert->start() ? Format::datetime($alert->start()) : '',
            $alert->end() ? Format::datetime($alert->end()) : '',
            $alert->render(),
            sprintf('<a href="%s" class="button button--small button--inverted">edit</a>', $alert->editUrl()),
        ];
    },
    [
        'Start',
        'End',
        'Alert',
        'Edit'
    ]
);