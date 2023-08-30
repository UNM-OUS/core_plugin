<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\People;

use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use Envms\FluentPDO\Queries\Select;

class Staff
{
    public static function select(): Select
    {
        return SharedDB::query()->from('staff');
    }
}