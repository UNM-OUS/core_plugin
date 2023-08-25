<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class AlertsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('site_alerts')
            ->addColumn('uuid', 'uuid', ['null' => false])
            ->addColumn('title', 'string', ['length' => 250, 'null' => false])
            ->addColumn('content', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR, 'null' => false])
            ->addColumn('class', 'string', ['length' => 250, 'null' => false])
            ->addColumn('start_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('end_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addIndex('uuid', ['unique' => true])
            ->addIndex('start_time')
            ->addIndex('end_time')
            ->create();
    }
}