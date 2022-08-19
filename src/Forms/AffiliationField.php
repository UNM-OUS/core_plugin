<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;

class AffiliationField extends FIELDSET
{
    protected $for;
    protected $required = false;
    protected $interface;

    public function __construct(string $for)
    {
        parent::__construct('UNM Affiliation');
        $this->for = $for;
        $this->setID('unm-affiliation-form--' . crc32($for));
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
        if ($this->required && !$this->value()['type']) {
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
        if ($this->value()['type']) {
            $out = '<div><strong>Organization:</strong> ' . $this->value()['type'] . '</div>';
            $out .= '<p><small><a href="' . new URL('&' . $this->id() . '=edit') . '">Edit affiliation information</a></small></p>';
            return $out;
        } else {
            return '<p><strong style="color:var(--cue-danger);">No information found</strong><br><a href="' . new URL('&' . $this->id() . '=edit') . '">Edit affiliation information before submitting</a></p>';
        }
    }

    protected function editInterface(): FormWrapper
    {
        $form = new FormWrapper('affiliation-edit-form--' . crc32($this->for));
        $form->button()->setText('Save changes');
        $form->addCallback(function () {
            $url = Context::url();
            $url->unsetArg($this->id());
            throw new RedirectException($url);
        });
        return $form;
    }

    public function value()
    {
        static $cache = [];
        $person = @$cache[$this->for] ?? $cache[$this->for] = PersonInfo::fetch($this->for);
        return [
            'type' => $person->type(),
            'org' => $person->org(),
            'department' => $person->department(),
        ];
    }
}
