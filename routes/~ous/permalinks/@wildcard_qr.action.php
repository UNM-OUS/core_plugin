<h1>Permalink QR code</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Permalinks\Permalinks;
use DigraphCMS_Plugins\unmous\ous_digraph_module\QrGenerator;

$pl = Permalinks::get(Context::url()->actionSuffix());

if (!$pl) {
    throw new HttpError(404, 'Permalink not found');
}

printf(
    '<p>This QR code redirects to <a href="%s" data-wayback-ignore="true"><kbd>%s</kbd></a>.<br>The permalink has been followed %s times.</p>',
    $pl->target(),
    $pl->target(),
    $pl->count()
);

printf('<p><strong>PNG version</strong><br><img src="%s"></p>', QrGenerator::pngFile($pl->url(), $pl->slug())->url());

printf('<p><a href="%s">SVG version</a></p>', QrGenerator::svgFile($pl->url(), $pl->slug())->url());
