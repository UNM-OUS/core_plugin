<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\HTML\Forms\INPUT;

class NetIDInput extends INPUT
{
    public function __construct(string $id = null, bool $allow_netid_extensions = false)
    {
        parent::__construct($id);
        if ($allow_netid_extensions) {
            $this->addValidator(Validation::netIdWithExtension());
        } else {
            $this->addValidator(Validation::netID());
        }
    }

    public function value(bool $useDefault = false): string|null
    {
        return preg_replace('/@unm\.edu$/', '', strtolower(parent::value($useDefault) ?? ''))
            ?: null;
    }

    public function netIdValue(bool $useDefault = false): string|null
    {
        return preg_replace('/\..*$/', '', $this->value($useDefault))
            ?: null;
    }

    public function extensionValue(bool $useDefault = false): string|null
    {
        return preg_replace('/^.*\./', '', $this->value($useDefault))
            ?: null;
    }
}
