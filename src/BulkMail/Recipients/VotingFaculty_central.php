<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

use DigraphCMS\Config;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use Envms\FluentPDO\Queries\Select;

class VotingFaculty_central extends VotingFaculty
{
    public function label(): string
    {
        return "Central Campus Voting Faculty";
    }

    protected function query(): Select
    {
        /** @var Select */
        $query = parent::query()->where(
            sprintf(
                'org NOT IN (%s)',
                implode(',', array_map(
                    SharedDB::query()->getPdo()->quote(...),
                    array_merge(Config::get('unm.branch_orgs'), Config::get('unm.hsc_orgs'), Config::get('unm.north_orgs'))
                )
                )
            )
        );
        return $query;
    }
}