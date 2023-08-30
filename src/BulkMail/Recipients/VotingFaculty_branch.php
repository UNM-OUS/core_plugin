<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

use DigraphCMS\Config;
use Envms\FluentPDO\Queries\Select;

class VotingFaculty_branch extends VotingFaculty
{
    public function label(): string
    {
        return "Branch Campus Voting Faculty";
    }

    protected function query(): Select
    {
        /** @var Select */
        $query = parent::query()
            ->where('org', Config::get('unm.branch_orgs'));
        return $query;
    }
}