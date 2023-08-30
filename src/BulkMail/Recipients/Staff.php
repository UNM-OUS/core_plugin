<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

use Envms\FluentPDO\Queries\Select;

class Staff extends AbstractSelectRecipientSource
{
    public function label(): string
    {
        return "Staff";
    }

    protected function query(): Select
    {
        /** @var Select */
        $query = \DigraphCMS_Plugins\unmous\ous_digraph_module\People\Staff::select()
            ->where('email is not null');
        return $query;
    }
}