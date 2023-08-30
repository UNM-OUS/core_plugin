<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

use Envms\FluentPDO\Queries\Select;

class AllFaculty extends AbstractSelectRecipientSource
{
    public function label(): string
    {
        return "All Faculty";
    }

    protected function query(): Select
    {
        /** @var Select */
        $query = \DigraphCMS_Plugins\unmous\ous_digraph_module\People\AllFaculty::select()
            ->where('email is not null');
        return $query;
    }
}