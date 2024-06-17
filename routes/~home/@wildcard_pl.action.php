<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\ArbitraryRedirectException;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\WaybackMachine;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Permalinks\Permalinks;

Context::response()->template('minimal.php');

$permalink = Permalinks::get(Context::url()->actionSuffix());
if (!$permalink) {
    throw new HttpError(404, 'Permalink not found');
}

// redirect if wayback machine doesn't have any problems with target URL
if (WaybackMachine::check($permalink->target())) {
    $permalink->increment();
    throw new ArbitraryRedirectException($permalink->target());
}

// otherwise, show a warning page
Context::response()->enableCache();

echo '<h1>Possible broken link ahead!</h1>';
printf(
    '<p>The page this URL was supposed to redirect to (<kbd>%s</kbd>) may be down. This check is automated and might be wrong, so you can ignore it and <a href="%s" data-wayback-ignore="true">go to the URL anyway</a>.</p>',
    $permalink->target(),
    $permalink->target(),
);

$wb = WaybackMachine::get($permalink->target());
if (!$wb) return;
?>
<p>
    A potentially relevant snapshot of the contents of the intended URL has been found in the <a href="https://web.archive.org/" target="_blank" data-wayback-ignore="true">Wayback Machine</a>, a database of archived web pages founded by the <a href="https://archive.org/" target="_blank" data-wayback-ignore="true">Internet Archive</a>.
</p>
<p>
    <a href="<?php echo $wb->wbURL(); ?>" class="button button--confirmation" data-wayback-ignore="true">
        View archived copy of
        <code style='color:inherit;background:transparent;'><?php echo htmlspecialchars($wb->originalURL()); ?></code>
    </a><br>
    <small>
        This snapshot was recorded <?php echo Format::date($wb->wbTime()); ?>.
    </small>
</p>