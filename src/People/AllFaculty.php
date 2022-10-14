<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\People;

use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

class AllFaculty
{
    public static function select()
    {
        return SharedDB::query()->from('all_faculty');
    }
}
