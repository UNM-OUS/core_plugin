<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\HTML\Forms\INPUT;

class NetIDInput extends INPUT
{
    public function __construct()
    {
        $this->addValidator(function () {
            if (!$this->value()) return null;
            // validate as NetID
            if (preg_match('/^[0-9]{9}$/', $this->value())) {
                return "Please enter a NetID username, not a Banner ID number";
            }
            if (!preg_match('/^[a-z].{1,19}$/', $this->value())) {
                return "NetIDs must be 2-20 characters and begin with a letter";
            }
            if (preg_match('/[^a-z0-9_]/', $this->value())) {
                return "NetIDs must contain only alphanumeric characters and underscores";
            }
            return null;
        });
    }

    public function value($useDefault = false)
    {
        return strtolower(parent::value($useDefault));
    }
}
