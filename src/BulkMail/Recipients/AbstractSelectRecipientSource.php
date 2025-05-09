<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

use Envms\FluentPDO\Queries\Select;

abstract class AbstractSelectRecipientSource extends AbstractRecipientSource
{
    abstract protected function query(): Select;

    public function recipients(): iterable
    {
        $query = $this->query();
        while ($person = $query->fetch()) {
            if (!$person['email']) continue;
            yield new Recipient($person['email']);
        }
    }

    public function count(): int
    {
        return $this->query()->count();
    }
}