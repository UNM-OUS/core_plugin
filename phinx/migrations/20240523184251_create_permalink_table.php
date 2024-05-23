<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePermalinkTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('permalink')
            ->addColumn('slug', 'uuid', ['null' => false])
            ->addColumn('target', 'string', ['length' => 500, 'null' => false])
            ->addColumn('count', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('created', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('created_by', 'uuid', ['null' => false])
            ->addColumn('updated', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('updated_by', 'uuid', ['null' => false])
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->addForeignKey(['updated_by'], 'user', ['uuid'])
            ->addIndex('slug', ['unique' => true])
            ->addIndex('target')
            ->create();
    }
}
