<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\People;

use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

class VotingFaculty
{
    public static function select()
    {
        return SharedDB::query()->from('voting_faculty');
    }
}
