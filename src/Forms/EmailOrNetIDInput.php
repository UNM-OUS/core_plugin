<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\HTML\Forms\INPUT;

class EmailOrNetIDInput extends INPUT
{
    public function __construct(string $id = null, bool $allow_netid_extensions = false)
    {
        parent::__construct($id);
        if ($allow_netid_extensions) {
            $this->addValidator(Validation::netIdWithExtensionOrEmail());
        } else {
            $this->addValidator(Validation::netIDorEmail());
        }
    }

    public function value(bool $useDefault = false): string|null
    {
        return preg_replace('/@unm\.edu$/', '', strtolower(parent::value($useDefault) ?? ''))
            ?: '';
    }

    public function netIdValue(bool $useDefault = false): string|null
    {
        if (str_contains($this->value($useDefault) ?? '', '@')) {
            return null;
        }
        return preg_replace('/\..*$/', '', $this->value($useDefault))
            ?: null;
    }

    public function extensionValue(bool $useDefault = false): string|null
    {
        if (str_contains($this->value($useDefault) ?? '', '@')) {
            return null;
        }
        return preg_replace('/^.*\./', '', $this->value($useDefault))
            ?: null;
    }

    public function emailValue(bool $useDefault = false): string|null
    {
        if (str_contains($this->value($useDefault) ?? '', '@')) {
            return $this->value($useDefault);
        }
        if ($netId = $this->netIdValue($useDefault)) {
            return $netId . '@unm.edu';
        }
        return null;
    }
}
