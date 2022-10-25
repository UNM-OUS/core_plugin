<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\Context;
use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteField;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteInput;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

class AffiliationField extends FIELDSET
{
    protected $for;
    protected $required = false;
    protected $interface;

    public function __construct(string $label = 'UNM affiliation', string $for)
    {
        parent::__construct($label);
        $this->for = $for;
        $this->setID('unm-affiliation-form--' . crc32($for));
    }

    /**
     * Undocumented function
     *
     * @param array|null $data
     * @return $this
     */
    public function setDefault(array $data = null)
    {
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param FormWrapper $form
     * @return $this
     */
    public function addForm(FormWrapper $form)
    {
        $form->addChild($this);
        return $this;
    }

    /**
     * @param boolean $required
     * @return $this
     */
    public function setRequired(bool $required)
    {
        $this->required = $required;
        return $this;
    }

    public function validationError(): ?string
    {
        if ($this->required && !$this->hasValue()) {
            $this->addChild('<div class="notification notification--error">This field is required</div>');
            return 'This field is required';
        } else {
            return null;
        }
    }

    public function children(): array
    {
        return array_merge(
            parent::children(),
            [
                $this->interface()
            ]
        );
    }

    public function interface(): DIV
    {
        return $this->interface
            ?? $this->interface = (new DIV)
            ->addClass('navigation-frame navigation-frame--stateless')
            ->setID('unm-affiliation-interface--' . crc32($this->for))
            ->addChild(
                (Context::arg($this->id()) == 'edit')
                    ? $this->editInterface()
                    : $this->displayInterface()
            );
    }

    protected function displayInterface(): string
    {
        if ($this->hasValue()) {
            $out = '<div><strong>Primary affiliation:</strong> ' . $this->value()['type'] . '</div>';
            if ($this->value()['org']) {
                $out .= '<div><strong>School/College/Organization:</strong> ' . $this->value()['org'] . '</div>';
            }
            if ($this->value()['department']) {
                $out .= '<div><strong>Department:</strong> ' . $this->value()['department'] . '</div>';
            }
            if (!@$this->value()['locked'] || Permissions::inMetaGroup('unmaffiliation__edit')) $out .= '<p><small><a href="' . new URL('&' . $this->id() . '=edit') . '">Edit affiliation information</a></small></p>';
            return $out;
        } else {
            return '<p><a href="' . new URL('&' . $this->id() . '=edit') . '" class="button">Enter affiliation information</a></p>';
        }
    }

    protected function editInterface(): FormWrapper
    {
        $form = new FormWrapper('affiliation-edit-form--' . crc32($this->id()));
        $form->button()->setText('Continue');

        $type = (new Field('Primary affiliation', new SELECT([
            'Faculty' => 'Faculty',
            'Staff' => 'Staff',
            'Student' => 'Student',
            'Alumni' => 'Alumni',
            'Regent' => 'Regent',
            'Upper administration' => 'Upper administration',
            'Other' => 'Other',
            'None' => 'None',
        ])))->setDefault(@$this->value()['type'])
            ->addForm($form);

        if ($type->value() && !in_array($type->value(), ['Student', 'Alumni', 'Regent', 'Upper administration', 'None'])) {
            $org = (new AutocompleteField(
                'School/College/Organization',
                (new AutocompleteInput(
                    null,
                    new URL('/~api/v1/unm-affiliation/org.php'),
                    function ($value) {
                        if (!$value) return null;
                        if ($value != 'Other' && !Permissions::inMetaGroup('unmaffiliation__edit')) {
                            $query = SharedDB::query()->from('person_info')
                                ->disableSmartJoin()
                                ->where(AbstractMappedSelect::parseJsonRefs('${data.affiliation.org}'), $value);
                            if (!$query->count()) return null;
                        }
                        return [
                            'html' => $value,
                            'value' => $value
                        ];
                    }
                ))->addClass('autocomplete-input--autopopulate')
            ))->setDefault(@$this->value()['org'])
                ->addForm($form);
        }

        if (isset($org) && $org->value() && !in_array($org->value(), ['Other'])) {
            $department = (new AutocompleteField(
                'Department',
                (new AutocompleteInput(
                    null,
                    new URL('/~api/v1/unm-affiliation/department.php?org=' . $org->value()),
                    function ($value) {
                        if (!$value) return null;
                        if (!Permissions::inMetaGroup('unmaffiliation__edit')) {
                            $query = SharedDB::query()->from('person_info')
                                ->disableSmartJoin()
                                ->where(AbstractMappedSelect::parseJsonRefs('${data.affiliation.department}'), $value);
                            if (!$query->count()) return null;
                        }
                        return [
                            'html' => $value,
                            'value' => $value
                        ];
                    }
                ))->addClass('autocomplete-input--autopopulate')
            ))->setDefault(@$this->value()['department'])
                ->addForm($form);
        }

        $form->addCallback(function () use ($type, $org, $department) {
            // verify that we have everything
            if (!$type->value()) return;
            if (isset($org) && !$org->value()) return;
            if (isset($department) && !$department->value()) return;
            // save data into personinfo
            PersonInfo::setFor($this->for, [
                'affiliation' => [
                    'type' => $type->value(),
                    'org' => isset($org) ? $org->value() ?? '' : '',
                    'department' => isset($department) ? $department->value() ?? '' : '',
                ]
            ]);
            // bounce away from editing form
            $url = Context::url();
            $url->unsetArg($this->id());
            throw new RedirectException($url);
        });
        return $form;
    }

    public function hasValue(): bool
    {
        return boolval(array_filter(
            $this->value() ?? [],
            'boolval'
        ));
    }

    public function value()
    {
        return PersonInfo::getFor($this->for, 'affiliation');
    }
}
