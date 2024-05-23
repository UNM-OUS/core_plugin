<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Permalinks;

use DigraphCMS\DB\AbstractMappedSelect;

/**
 * @extends AbstractMappedSelect<Permalink>
 */
class PermalinkSelect extends AbstractMappedSelect
{
    protected $returnObjectClass = Permalink::class;
}
