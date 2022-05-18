<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\Fields\CheckboxListField;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS\HTML\Forms\Phone;
use DigraphCMS\HTML\Forms\TEXTAREA;

class AccommodationsField extends FIELDSET
{
    protected $requested, $needs, $extraRequest, $phone;

    public function __construct(string $label = null, bool $phone = false)
    {
        parent::__construct($label ?? 'Special accommodations');
        // set up fields
        $this->requested = new CheckboxField('I require special accommodations');
        $this->needs = new CheckboxListField('Accommodations required', [
            'wheelchair' => 'Wheelchair access',
            'stairs' => 'Inability to negotiate stairs',
            'mobility' => 'Use of cane, walker, or crutches',
            'asl' => 'Requires signed language interpreter',
            'other' => 'I require an accommodation not listed here'
        ]);
        $this->extraRequest = new Field('Please specify any accommodations you require', new TEXTAREA);
        $this->phone = $phone
            ? (new Field('Phone number', new Phone))
            ->addClass('accommodations-field__phone')
            ->addTip('We will use this phone number to contact you if necessary to coordinate accommodations')
            : null;
        // set up classes
        $this->requested->addClass('accommodations-field__requested');
        $this->needs->addClass('accommodations-field__needs');
        $this->extraRequest->addClass('accommodations-field__extra-request');
        // set up validation
        $this->needs->addValidator(function () {
            if ($this->requested && !$this->needs->value()) return "Please select the accommodations you require";
            return null;
        });
        $this->extraRequest->addValidator(function () {
            if (in_array('other', $this->needs->value()) && !$this->extraRequest->value()) return "Please indicate the accommodations you require";
            return null;
        });
        if ($this->phone) {
            $this->phone->addValidator(function () {
                if ($this->requested && !$this->phone->value()) return "Please provide a phone number so that we can contact you regarding your accommodations";
                return null;
            });
        }
    }

    /**
     * Set the default values using an array the same shape as what is returned
     * by value()
     *
     * @param array|null $value
     * @return $this
     */
    public function setDefault(array $value = null)
    {
        $value = $value ?? [];
        $this->requested->setDefault(@$value['requested'] ?? false);
        $this->needs->setDefault(@$value['needs'] ?? []);
        if (@$value['extra']) $this->extra->setDefault($value['extra']);
        if ($this->phone && @$value['phone']) $this->phone->setDefault($value['phone']);
        return $this;
    }

    public function value($useDefault = false): ?array
    {
        if (!$this->requested->value($useDefault)) return null;
        return array_filter(
            [
                'requested' => $this->requested->value($useDefault),
                'needs' => $this->needs->value($useDefault),
                'extra' => $this->extraRequest->value($useDefault),
                'phone' => $this->phone->value($useDefault)
            ],
            function ($e) {
                return !!$e;
            }
        );
    }

    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            [
                'accommodations-field'
            ]
        );
    }

    public function children(): array
    {
        return array_merge(
            parent::children(),
            [
                $this->requested,
                $this->phone ?? '',
                $this->needs,
                $this->extraRequest,
            ]
        );
    }
}
