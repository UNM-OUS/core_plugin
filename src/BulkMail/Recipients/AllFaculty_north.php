<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

use DigraphCMS\Config;
use Envms\FluentPDO\Queries\Select;

class AllFaculty_north extends AllFaculty
{
    public function label(): string
    {
        return "North Campus All Faculty";
    }

    protected function query(): Select
    {
        /** @var Select */
        $query = parent::query()
            ->where(
                'org',
                Config::get('unm.north_orgs')
            );
        return $query;
    }
}