<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;

class AccommodationsField extends FIELDSET
{
    protected $for;
    protected $input;

    public function __construct(string $for)
    {
        $this->for = $for;
        $this->addClass('navigation-frame navigation-frame--stateless');
        $this->setID('unm-affiliation-form--' . crc32($for));
    }

    public function value()
    {
        if ($person = PersonInfo::fetch($this->for)) {
            return [
                'type' => $person->type(),
                'org' => $person->org(),
                'department' => $person->department(),
            ];
        } else {
            return null;
        }
    }
}
