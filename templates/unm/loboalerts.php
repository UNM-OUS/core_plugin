<?php

use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\AlertBanner;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\AlertBanners;

$alerts = AlertBanners::alerts();

if ($alerts) {
    echo '<div id="loboalerts">';
    /** @var AlertBanner $alert */
    foreach ($alerts as $alert) {
        echo $alert->render();
    }
    echo '</div>';
}