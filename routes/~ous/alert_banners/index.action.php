<h1>Sitewide alert banners</h1>
<p>
    These banners can be used to display warnings or notifications site-wide.
    There are also "global" alerts that when posted will appear on <strong>all OUS websites</strong>.
    Banners can also be scheduled to start and end at specific times.
</p>
<?php

use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;

ActionMenu::hide();
Notifications::printNotice('Changes to banners may take some time to appear or disappear from all pages, and will almost always take up to 5 minutes');

echo Templates::render(
    'content/toc.php',
);