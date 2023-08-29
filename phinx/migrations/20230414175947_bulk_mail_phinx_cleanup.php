<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * This migration is being added to coincide with updating to Phinx 0.13, to
 * help make sure all the index columns line up with the new default types.
 */
final class BulkMailPhinxCleanup extends AbstractMigration
{
    public function change(): void
    {
        // drop all foreign keys to primary key id columns
        $foreign_keys = [
            'bulk_mail_message' => ['column' => 'bulk_mail_id', 'table' => 'bulk_mail'],
        ];
        foreach ($foreign_keys as $table => $key) {
            $this->table($table)
                ->dropForeignKey($key['column'])
                ->save();
        }
        // update all the primary key id columns
        $primary_keys = [
            'bulk_mail', 'bulk_mail_message'
        ];
        foreach ($primary_keys as $table) {
            $this->table($table)
                ->changeColumn('id', 'integer', ['signed' => false, 'null' => false, 'identity' => true])
                ->changePrimaryKey('id')
                ->save();
        }
        // re-add all the foreign keys that reference primary key id columns
        foreach ($foreign_keys as $table => $key) {
            $this->table($table)
                ->changeColumn($key['column'], 'integer', ['null' => false, 'signed' => false])
                ->addForeignKey($key['column'], $key['table'])
                ->save();
        }
    }
}
