<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\HTML\Forms\INPUT;

class NetIDInput extends INPUT
{
    public function __construct()
    {
        $this->addValidator(Validation::netID());
    }

    public function value(bool $useDefault = false): string
    {
        return preg_replace('/@unm\.edu$/', '', strtolower(parent::value($useDefault) ?? ''));
    }
}
