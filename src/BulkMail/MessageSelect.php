<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail;

use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\DB\DB;

class MessageSelect extends AbstractMappedSelect
{
    /** @var Mailing */
    protected $mailing;
    protected $returnObjectClass = Message::class;

    public function __construct(Mailing $mailing = null)
    {
        $this->mailing = $mailing;
        $query = DB::query()->from('bulk_mail_message');
        if ($mailing) $query->where('bulk_mail_id', $mailing->id());
        parent::__construct($query);
    }
}
