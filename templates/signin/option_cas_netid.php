<?php

use DigraphCMS\Context;
use DigraphCMS\URL\URL;

/** @var URL */
$url = Context::fields()['url'];

?>

<a href="<?= $url ?>">
    UNM NetID sign in
</a>
<div class="small">
    Your main-campus NetID, the same username and password you would use to sign into my.unm.edu
</div>