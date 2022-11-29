<?php

use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\LoboAlerts;

$alerts = LoboAlerts::alerts();

if ($alerts) {
    echo '<div id="loboalerts">';
    foreach ($alerts as $alert) {
        echo $alert->render();
    }
    echo '</div>';
}
