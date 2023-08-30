<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

use DigraphCMS\Config;
use Envms\FluentPDO\Queries\Select;

class Staff_north extends Staff
{
    public function label(): string
    {
        return "North Campus Staff";
    }

    protected function query(): Select
    {
        /** @var Select */
        $query = parent::query()
            ->where(
                'org',
                array_merge(Config::get('unm.hsc_orgs'), Config::get('unm.north_orgs'))
            );
        return $query;
    }
}