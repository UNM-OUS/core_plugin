<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\HTML\Forms\INPUT;

class BillingIndexInput extends INPUT
{
    public function __construct(
        string $id = null,
        protected bool $accountCodeEnabled = false,
        protected string $defaultAccountCode = null,
    ) {
        parent::__construct($id);
        $this->addValidator(function () {
            if (!$this->value()) return null;
            if ($this->accountCodeEnabled()) {
                return preg_match('/^[0-9A-Z]{6}(\-[0-9A-Z]{4})?$/', $this->value())
                    ? null
                    : "Please enter a valid six-digit alphanumeric billing index, optionally including a four-digit account code";
            } else {
                return preg_match('/^[0-9A-Z]{6}$/', $this->value())
                    ? null
                    : "Please enter a valid six-digit alphanumeric billing index, optionally including a four-digit account code";
            }
        });
    }

    public function value(bool $useDefault = false): string|null
    {
        // strip to alphanumeric characters and force upper-case
        /** @var string */
        $value = strtoupper(parent::value($useDefault) ?? '');
        $value = preg_replace('/[^0-9A-Z]/', '', $value) ?? '';
        // format properly if account code is enabled
        if ($this->accountCodeEnabled() && strlen($value) == 10) {
            // append default account code if it is enabled and set
            if ($this->defaultAccountCode()) {
                $value .= $this->defaultAccountCode();
            }
            // return formatted value
            return sprintf(
                '%s-%s',
                substr($value, 0, 6),
                substr($value, 6, 4)
            );
        }
        // return unformatted value if it isn't valid
        return $value ? $value : null;
    }

    public function accountCodeEnabled(): bool
    {
        return $this->accountCodeEnabled;
    }

    public function setAccountCodeEnabled(bool $accountCodeEnabled): static
    {
        $this->accountCodeEnabled = $accountCodeEnabled;
        return $this;
    }

    public function defaultAccountCode(): ?string
    {
        return $this->defaultAccountCode;
    }

    public function setDefaultAccountCode(string $defaultAccountCode): static
    {
        $this->defaultAccountCode = $defaultAccountCode;
        return $this;
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'maxlength' => 11
            ]
        );
    }
}