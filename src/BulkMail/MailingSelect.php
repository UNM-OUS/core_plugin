<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail;

use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\DB\DB;
use DigraphCMS\Users\Permissions;

class MailingSelect extends AbstractMappedSelect
{
    protected $returnObjectClass = Mailing::class;

    public function __construct()
    {
        $query = DB::query()->from('bulk_mail');
        if (!Permissions::inMetaGroup('bulk_mail__admin')) {
            $query->where('category', array_keys(BulkMail::categories()));
        }
        parent::__construct($query);
    }
}
