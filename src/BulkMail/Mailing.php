<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients\AbstractRecipientSource;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients\Recipient;

class Mailing
{
    /** @var int */
    protected $id;
    /** @var string */
    protected $name;
    /** @var string */
    protected $from;
    /** @var string */
    protected $subject;
    /** @var string */
    protected $body;
    /** @var string */
    protected $sources;
    /** @var string */
    protected $extra_recipients;
    /** @var string */
    protected $category;
    /** @var int|null */
    protected $sent;
    /** @var string|null */
    protected $sent_by;
    /** @var int */
    protected $created;
    /** @var string */
    protected $created_by;
    /** @var int */
    protected $updated;
    /** @var string */
    protected $updated_by;

    public function addRecipient(Recipient $recipient): void
    {
        $check = DB::query()->from('bulk_mail_message')
            ->where('bulk_mail_id', $this->id())
            ->where('email', $recipient->email())
            ->count();
        if ($check) {
            // update user ID if specified, only for unsent messages
            if ($recipient->userUuid()) {
                DB::query()->update('bulk_mail_message', [
                    'bulk_mail_id' => $this->id(),
                    'email' => $recipient->email(),
                    'user' => $recipient->userUuid(),
                    'sent' => null
                ])
                    ->where('bulk_mail_id', $this->id())
                    ->where('sent is null')
                    ->where('email', $recipient->email())
                    ->execute();
            }
            return;
        }
        // add new message
        DB::query()->insertInto('bulk_mail_message', [
            'bulk_mail_id' => $this->id(),
            'email' => $recipient->email(),
            'user' => $recipient->userUuid(),
            'sent' => null
        ])->execute();
    }

    /** @return string[] */
    public function extraRecipientAddresses(): array
    {
        return array_filter(
            array_map(
                function (string $line): string {
                    return strtolower(trim($line));
                },
                // @phpstan-ignore-next-line
                preg_split("/\r\n|\n|\r/", $this->extraRecipients())
            ),
            function (string $line): bool {
                return !!filter_var($line, FILTER_VALIDATE_EMAIL);
            }
        );
    }

    public function extraRecipients(): string
    {
        return $this->extra_recipients;
    }

    /**
     * @return array<int,string>
     */
    public function sourceNames(): array
    {
        return explode(',', $this->sources);
    }

    /**
     * @return array<int,AbstractRecipientSource>
     */
    public function sources(): array
    {
        return array_filter(array_map(
            function (string $name): ?AbstractRecipientSource {
                return BulkMail::source($name);
            },
            $this->sourceNames()
        ));
    }

    public function messages(): MessageSelect
    {
        return new MessageSelect($this);
    }

    public function messageCount(): int
    {
        return $this->messages()->count();
    }

    public function body(): string
    {
        return $this->body;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function from(): string
    {
        return $this->from;
    }

    public function createdBy(): User
    {
        return Users::user($this->created_by);
    }

    public function updatedBy(): User
    {
        return Users::user($this->updated_by);
    }

    public function sentBy(): ?User
    {
        if (!$this->sent_by) return null;
        return Users::user($this->sent_by);
    }

    public function created(): DateTime
    {
        return (new DateTime)->setTimestamp($this->created);
    }

    public function updated(): DateTime
    {
        return (new DateTime)->setTimestamp($this->updated);
    }

    public function sent(): ?DateTime
    {
        if (!$this->sent) return null;
        return (new DateTime)->setTimestamp($this->sent);
    }

    public function editUrl(): URL
    {
        return (new URL('/bulk_mail/edit:' . $this->id))
            ->setName($this->name());
    }

    public function sendUrl(): URL
    {
        return (new URL('/bulk_mail/send:' . $this->id))
            ->setName('Send: ' . $this->name());
    }

    public function deleteUrl(): URL
    {
        return (new URL('/bulk_mail/delete:' . $this->id))
            ->setName('Delete: ' . $this->name());
    }

    public function previewUrl(): URL
    {
        return (new URL('/bulk_mail/preview:' . $this->id))
            ->setName($this->name());
    }

    public function recipientsUrl(): URL
    {
        return (new URL('/bulk_mail/recipients:' . $this->id))
            ->setName('Recipients: ' . $this->name());
    }

    public function messagesUrl(): URL
    {
        return (new URL('/bulk_mail/messages:' . $this->id))
            ->setName('Messages: ' . $this->name());
    }

    public function sourceUrl(): URL
    {
        return (new URL('/bulk_mail/source:' . $this->id))
            ->setName('Source: ' . $this->name());
    }

    public function copyUrl(): URL
    {
        return (new URL('/bulk_mail/copy:' . $this->id))
            ->setName('Copy: ' . $this->name());
    }

    public function name(): string
    {
        return $this->name;
    }

    public function id(): int
    {
        return $this->id;
    }
}
