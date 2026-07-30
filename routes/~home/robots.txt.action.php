<?php

use DigraphCMS\Context;

Context::response()->filename('robots.txt');
?>
User-agent: *
Disallow:
Crawl-delay: 5
Disallow: /cgi-bin/
Disallow: /color_settings/
Disallow: /api/
Disallow: /signin/
Disallow: /login/
Disallow: /user_settings/