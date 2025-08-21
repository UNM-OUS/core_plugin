<?php
declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class BulkMailSchedulingColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('bulk_mail')
            ->removeIndex('scheduled')
            ->removeColumn('scheduled')
            ->addColumn('schedule', 'text', ['null' => false, 'default' => '', 'limit' => MysqlAdapter::TEXT_LONG])
            ->save();
    }
}
