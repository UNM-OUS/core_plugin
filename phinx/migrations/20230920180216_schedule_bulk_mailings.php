<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ScheduleBulkMailings extends AbstractMigration
{
    public function change(): void
    {
        $this->table('bulk_mail')
            ->addColumn('scheduled', 'biginteger', ['null' => true, 'signed' => false])
            ->addIndex('scheduled')
            ->save();
    }
}
