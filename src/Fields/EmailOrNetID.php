<?php
namespace Digraph\Modules\ous_digraph_module\Fields;

use Formward\FieldInterface;
use Formward\Fields\Input;

class EmailOrNetID extends Input
{
    public function __construct(string $label, string $name=null, FieldInterface $parent=null)
    {
        parent::__construct($label, $name, $parent);
        $this->addTip('Enter a valid email address or <em>main campus</em> NetID.');
        $this->addValidatorFunction(
            'validnetid',
            function ($field) {
                if (!$field->value()) {
                    return true;
                }
                if (strpos($field->value(),'@') === false) {
                    // no @ sign, validate as NetID
                    if (!preg_match('/^[a-z].{1,19}$/', $field->value())) {
                        return "NetIDs must be 2-20 characters and begin with a letter.";
                    }
                    if (preg_match('/[^a-z0-9_]/', $field->value())) {
                        return "NetIDs must contain only alphanumeric characters and underscores.";
                    }
                }else {
                    // there is an @ sign, validate as email
                    if (!filter_var($field->value(),FILTER_VALIDATE_EMAIL)) {
                        return 'Please enter a valid NetID or email address.';
                    }
                }
                return true;
            }
        );
    }

    public function value($set=null)
    {
        $value = strtolower(parent::value($set));
        $value = preg_replace('/@unm\.edu$/','',$value);
        return $value;
    }

    public function default($set=null)
    {
        return strtolower(parent::default($set));
    }
}
