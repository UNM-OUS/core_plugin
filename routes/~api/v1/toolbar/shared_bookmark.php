<?php

use DigraphCMS\Digraph;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteInput;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\URL\URL;

echo new ToolbarLink('', 'link', null, null);
$wrapper = (new DIV())->setID(Digraph::uuid())->setStyle('width', '100%');
$input = (new AutocompleteInput(Digraph::uuid(), new URL('/api/v1/autocomplete/shared_bookmark.php')))
    ->addClass('navigation-frame__autofocus');
$wrapper->addChild($input);
echo $wrapper;

?>
<script>
    (() => {
        var ac = document.getElementById('<?php echo $wrapper->id(); ?>');
        // select value and reset toolbar
        ac.addEventListener('autocomplete-select', (e) => {
            ac.dispatchEvent(Digraph.RichContent.insertTagEvent(e.autocompleteExtra.category, {
                _: e.autocompleteValue
            }));
            ac.dispatchEvent(new Event('navigation-frame-reset', {
                bubbles: true
            }));
        });
    })();
</script>