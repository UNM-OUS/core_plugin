<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
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
        $query = SharedDB::query()
            ->from('faculty_list')
            ->where('voting', true)
            ->where('email is not null');
        return $query;
    }
}
