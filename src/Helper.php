<?php
namespace Digraph\Modules\ous_digraph_module;

class Helper extends \Digraph\Helpers\AbstractHelper
{
    public function initialize()
    {
        $this->cms->helper('forms')->registerType(
            'netid',
            Fields\NetID::class
        );
        $this->cms->helper('forms')->registerType(
            'semester',
            Fields\SemesterField::class
        );
        $this->cms->helper('forms')->registerType(
            'college',
            Fields\College::class
        );
    }
}
