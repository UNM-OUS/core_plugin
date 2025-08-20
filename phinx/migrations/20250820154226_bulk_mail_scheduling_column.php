<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BulkMailSchedulingColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('bulk_mail')
            ->removeIndex('scheduled')
            ->removeColumn('scheduled')
            ->addColumn('data', 'json', ['null' => false, 'default' => '[]'])
            ->save();
    }
}
