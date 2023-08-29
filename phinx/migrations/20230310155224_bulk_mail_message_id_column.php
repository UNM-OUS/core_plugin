<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BulkMailMessageIdColumn extends AbstractMigration
{
    public function change(): void
    {
        // Note that the email_uuid column is not indexed or a foreign key
        // this lets emails be cleared from the main system without breaking anything here,
        // if emails are cleared out from the main system, the bulk mail system will
        // simply not display their links/status
        $this->table('bulk_mail')
            ->addColumn('email_uuid', 'uuid', ['null' => true])
            ->save();
    }
}
