<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

use DigraphCMS\Config;
use Envms\FluentPDO\Queries\Select;

class Staff_branch extends Staff
{
    public function label(): string
    {
        return "Branch Campus Staff";
    }

    protected function query(): Select
    {
        /** @var Select */
        $query = parent::query()
            ->where('org', Config::get('unm.branch_orgs'));
        return $query;
    }
}