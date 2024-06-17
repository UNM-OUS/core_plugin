<h1>Global banners</h1>
<p>
    Banners created here will appear on <strong>all OUS sites</strong>.
</p>
<?php
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB\GlobalAlert;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB\GlobalAlerts;

echo "<h2>Current</h2>";
echo new PaginatedTable(
    GlobalAlerts::new()
        ->currentAlerts()
        ->order('end_time asc'),
    function (GlobalAlert $alert): array {
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
    GlobalAlerts::new()
        ->upcoming()
        ->order('start_time asc'),
    function (GlobalAlert $alert): array {
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
    GlobalAlerts::new()
        ->past()
        ->order('end_time desc'),
    function (GlobalAlert $alert): array {
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