<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\HTML\Forms\INPUT;

class EmailOrNetIDInput extends INPUT
{
    public function __construct()
    {
        $this->addValidator(function () {
            if (!$this->value()) return null;
            if (strpos($this->value(), '@') !== false) {
                // validate as email
                if (!filter_var($this->value(), FILTER_VALIDATE_EMAIL)) {
                    return "Please enter a valid email address or NetID";
                }
                // disallow alternate unm emails
                if (preg_match('/@.+\.unm\.edu$/', $this->value(), $matches)) {
                    return "Anyone associated with UNM should be referenced by their main campus NetID, not their <em>" . $matches[0] . "</em> email address. This is in many cases important for data consistency and login system integrations.";
                }
            } else {
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
            }
            return null;
        });
    }

    public function value($useDefault = false)
    {
        return preg_replace('/@unm\.edu$/', '', strtolower(parent::value($useDefault)));
    }
}
