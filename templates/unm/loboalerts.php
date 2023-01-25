<?php

use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\LoboAlert;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\LoboAlerts;

$alerts = LoboAlerts::alerts();

if ($alerts) {
    echo '<div id="loboalerts">';
    /** @var LoboAlert $alert */
    foreach ($alerts as $alert) {
?>
        <div class="loboalert loboalert--<?php echo $alert->class(); ?>" id="<?php echo $alert->id(); ?>">
            <div class="loboalert__title"><?php echo $alert->title(); ?></div>
            <a class="loboalert__expand" href="#<?php echo $alert->id(); ?>">-- read more --</a>
            <div class="loboalert__content"><?php echo $alert->content(); ?></div>
            <a class="loboalert__collapse" href="#">-- collapse --</a>
        </div>
<?php
    }
    echo '</div>';
}
