<?php
namespace Digraph\Modules\ous_digraph_module\Fields;

use Digraph\CMS;
use Formward\FieldInterface;
use Formward\Fields\Number;
use Formward\Fields\Select;
use Formward\Fields\Container;

class SemesterField extends Container
{
    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS &$cms=null)
    {
        parent::__construct($label, $name, $parent);
        $this['semester'] = new Select('Semester');
        $this['semester']->options([
            'Fall' => 'Fall',
            'Spring' => 'Spring',
            'Summer' => 'Summer'
        ]);
        $this['year'] = new Number('Year');
    }
}
