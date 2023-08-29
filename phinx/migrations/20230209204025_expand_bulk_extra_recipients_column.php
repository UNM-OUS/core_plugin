<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ExpandBulkExtraRecipientsColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('bulk_mail')
            ->changeColumn('extra_recipients', 'text', ['limit' => MysqlAdapter::TEXT_LONG])
            ->save();
    }
}
