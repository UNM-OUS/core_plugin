<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail;

use DateTime;
use DigraphCMS\Context;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\DB\DB;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Format;
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
    /** @var int|null */
    protected $scheduled;

    public function send(): DeferredJob|null
    {
        if ($this->sent()) return null;
        DB::query()->update(
            'bulk_mail',
            [
                'sent' => time(),
                'sent_by' => Session::uuid(),
            ],
            $this->id()
        )->execute();
        $id = $this->id();
        return new DeferredJob(function (DeferredJob $job) use ($id) {
            $job->spawn(function (DeferredJob $job) use ($id) {
                return static::rebuildRecipientJob($job, $id);
            });
            $job->spawn(function (DeferredJob $job) use ($id) {
                return static::sendMailingJob($job, $id);
            });
        });
    }

    public static function rebuildRecipientJob(DeferredJob $job, int $id): string
    {
        $mailing = BulkMail::mailing($id);
        if (!$mailing) return "Mailing $id not found";
        $mailing->rebuildRecipients();
        return 'Rebuilt recipient list';
    }

    public static function sendMailingJob(DeferredJob $job, int $id): string
    {
        $mailing = BulkMail::mailing($id);
        if (!$mailing) return "Mailing $id not found";
        $messages = DB::query()
            ->from('bulk_mail_message')
            ->where('bulk_mail_id', $mailing->id())
            ->where('sent is null');
        while ($message = $messages->fetch()) {
            $id = intval($message['id']);
            $job->spawn(function () use ($id) {
                return static::sendMessageJob($id);
            });
        }
        return 'Prepared message-building jobs for "' . $mailing->name() . '"';
    }

    public static function sendMessageJob(int $id): string
    {
        $message = BulkMail::message($id);
        if (!$message) return "Message $id not found";
        $mailing = $message->mailing();
        Context::beginEmail();
        Context::fields()['bulk_mail'] = [
            'email' => $message->email(),
            'user' => $message->user()
        ];
        $email = new Email(
            $mailing->category(),
            $mailing->subject(),
            $message->email(),
            $message->user() ? $message->user()->uuid() : null,
            $mailing->from(),
            new RichContent($mailing->body())
        );
        Emails::queue($email);
        DB::query()
            ->update(
                'bulk_mail_message',
                [
                    'sent' => time(),
                    'email_uuid' => $email->uuid()
                ],
                $message->id()
            )
            ->execute();
        Context::end();
        return 'Queued emails for bulk message #' . $message->id();
    }

    public function rebuildRecipients(): static
    {
        // clear messages
        DB::query()
            ->delete('bulk_mail_message')
            ->where('bulk_mail_id', $this->id())
            ->where('sent is null')
            ->execute();
        // add messages from sources
        foreach ($this->sources() as $source) {
            foreach ($source->recipients() as $recipient) {
                $this->addRecipient($recipient);
            }
        }
        // add messages from extra recipients
        foreach ($this->extraRecipientAddresses() as $email) {
            $this->addRecipient(new Recipient($email));
        }
        return $this;
    }

    public function addRecipient(Recipient $recipient): static
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
            return $this;
        }
        // add new message
        DB::query()->insertInto('bulk_mail_message', [
            'bulk_mail_id' => $this->id(),
            'email' => $recipient->email(),
            'user' => $recipient->userUuid(),
            'sent' => null
        ])->execute();
        return $this;
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

    public function setScheduled(DateTime|int|null $scheduled): static
    {

        if (is_null($scheduled)) {
            $this->scheduled = null;
            return $this;
        }
        if (!($scheduled instanceof DateTime)) $scheduled = Format::parseDate($scheduled);
        $this->scheduled = $scheduled->getTimestamp();
        return $this;
    }

    public function scheduled(): DateTime|null
    {
        if (is_null($this->scheduled)) return null;
        else return (new DateTime)->setTimestamp($this->created);
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
