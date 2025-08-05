<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\CustomLists;

use DateTime;
use DigraphCMS\Datastore\DatastoreItem;
use DigraphCMS\Users\User;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients\AbstractRecipientSource;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients\Recipient;

class CustomRecipientList extends AbstractRecipientSource
{
    public function __construct(protected DatastoreItem $data)
    {
        parent::__construct('custom/' . $data->key());
    }

    public function recipients(): iterable
    {
        foreach ($this->data->data()['recipients'] as $email) {
            yield new Recipient($email);
        }
    }

    /**
     * @return array<string>
     */
    public function emails(): array
    {
        return $this->data->data()['recipients'];
    }

    /**
     * @param array<string> $recipient_emails
     */
    public function replaceRecipients(array $recipient_emails): static
    {
        // clean up and filter emails
        $recipient_emails = array_map(
            function (string $line): string {
                return strtolower(trim($line));
            },
            $recipient_emails
        );
        $recipient_emails = array_filter(
            $recipient_emails,
            function (string $email): bool {
                return boolval(filter_var($email, FILTER_VALIDATE_EMAIL));
            }
        );
        $recipient_emails = array_unique($recipient_emails);
        // set into data
        $this->data->data()->unset('recipients');
        $this->data->data()->set('recipients', $recipient_emails);
        return $this;
    }

    public function update(): bool
    {
        return $this->data->update();
    }

    public function delete(): bool
    {
        return $this->data->delete();
    }

    public function uuid(): string
    {
        return $this->data->key();
    }

    public function count(): int
    {
        return count($this->data->data()->get('recipients'));
    }

    public function label(): string
    {
        return $this->data->value();
    }

    public function created(): DateTime
    {
        return $this->data->created();
    }

    public function createdBy(): User
    {
        return $this->data->createdBy();
    }

    public function updated(): DateTime
    {
        return $this->data->updated();
    }

    public function updatedBy(): User
    {
        return $this->data->updatedBy();
    }
}