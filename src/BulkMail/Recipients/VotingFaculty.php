<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

use Envms\FluentPDO\Queries\Select;

class VotingFaculty extends AbstractSelectRecipientSource
{
    public function label(): string
    {
        return "Voting Faculty";
    }

    protected function query(): Select
    {
        /** @var Select */
        $query = \DigraphCMS_Plugins\unmous\ous_digraph_module\People\VotingFaculty::select()
            ->where('email is not null');
        return $query;
    }
}