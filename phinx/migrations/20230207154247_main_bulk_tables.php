<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class MainBulkTables extends AbstractMigration
{
    public function change(): void
    {
        $this->table('bulk_mail')
            ->addColumn('name', 'string', ['length' => 150, 'null' => false])
            ->addColumn('from', 'string', ['length' => 250, 'null' => false])
            ->addColumn('subject', 'string', ['length' => 150, 'null' => false])
            ->addColumn('body', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR, 'null' => false])
            ->addColumn('sources', 'string', ['length' => 1000, 'null' => false])
            ->addColumn('extra_recipients', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR, 'null' => false])
            ->addColumn('category', 'string', ['length' => 150, 'null' => false])
            ->addColumn('created', 'integer', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => false])
            ->addColumn('updated', 'integer', ['null' => false])
            ->addColumn('updated_by', 'uuid', ['null' => false])
            ->addColumn('sent', 'integer', ['null' => true])
            ->addColumn('sent_by', 'uuid', ['null' => true])
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->addForeignKey(['updated_by'], 'user', ['uuid'])
            ->addForeignKey(['sent_by'], 'user', ['uuid'])
            ->addIndex('created')
            ->addIndex('updated')
            ->addIndex('sent')
            ->addIndex('category')
            ->create();
        $this->table('bulk_mail_message')
            ->addColumn('bulk_mail_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('email', 'string', ['length' => 250, 'null' => false])
            ->addColumn('user', 'uuid', ['null' => true])
            ->addColumn('sent', 'integer', ['null' => true])
            ->addForeignKey(['bulk_mail_id'], 'bulk_mail', ['id'])
            ->addForeignKey(['user'], 'user', ['uuid'])
            ->addIndex('email')
            ->addIndex('sent')
            ->create();
    }
}
