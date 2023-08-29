<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BulkMailIntColumnFixes extends AbstractMigration
{
    public function change(): void
    {
        $update = [
            'bulk_mail' => [
                'null' => ['sent'],
                'notnull' => ['created', 'updated']
            ],
            'bulk_mail_message' => [
                'null' => ['sent'],
                'notnull' => []
            ],
        ];
        foreach ($update as $table => $groups) {
            $table = $this->table($table);
            foreach ($groups['null'] as $column) {
                $table->changeColumn($column, 'biginteger', ['null' => true, 'signed' => false]);
            }
            foreach ($groups['notnull'] as $column) {
                $table->changeColumn($column, 'biginteger', ['null' => false, 'signed' => false]);
            }
            $table->save();
        }
    }
}
