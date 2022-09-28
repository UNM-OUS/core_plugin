<h1>Manage lists of people</h1>
<p>
    These tools are for maintaining and using the global lists of UNM faculty, voting faculty, and staff.
    Updates here will be shared across all OUS sites, and will update the person info database at the same time.
</p>
<?php

use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Templates;

ActionMenu::hide();

echo Templates::render(
    'content/toc.php',
);
