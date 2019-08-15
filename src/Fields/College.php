<?php
namespace Digraph\Modules\ous_digraph_module\Fields;

use Digraph\CMS;
use Formward\FieldInterface;
use Formward\Fields\Number;
use Formward\Fields\Select;
use Formward\Fields\Container;

class College extends Select
{
    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS &$cms=null)
    {
        parent::__construct($label, $name, $parent);
        $this->options([
            "Other/Administrative" => "Other/Administrative",
            "Anderson School of Management" => "Anderson School of Management",
            "College of Arts & Sciences" => "College of Arts & Sciences",
            "College of Education" => "College of Education",
            "College of Fine Arts" => "College of Fine Arts",
            "Graduate Studies" => "Graduate Studies",
            "Honors College" => "Honors College",
            "College of Nursing" => "College of Nursing",
            "College of Pharmacy" => "College of Pharmacy",
            "College of Population Health" => "College of Population Health",
            "College of University Libraries & Learning Sciences" => "College of University Libraries & Learning Sciences",
            "School of Architecture and Planning" => "School of Architecture and Planning",
            "School of Engineering" => "School of Engineering",
            "School of Law" => "School of Law",
            "School of Medicine" => "School of Medicine",
            "University College" => "University College",
            "UNM-Gallup" => "UNM-Gallup",
            "UNM-Los Alamos" => "UNM-Los Alamos",
            "UNM-Taos" => "UNM-Taos",
            "UNM-Valencia" => "UNM-Valencia",
        ]);
    }
}
