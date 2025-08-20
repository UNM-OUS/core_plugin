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
use Flatrr\FlatArray;
use InvalidArgumentException;

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
    protected string $data;
    protected FlatArray|null $data_object = null;

    /**
     * Spawn a job to send a bulk mailing, which will rebuild recipients and
     * then queue all messages. If mailing has already been sent, it does
     * nothing and returns null.
     *
     * @return DeferredJob|null
     */
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

    public function sent(): ?DateTime
    {
        if (!$this->sent) return null;
        return (new DateTime)->setTimestamp($this->sent);
    }

    public function update(): bool
    {
        return DB::query()->update(
            'bulk_mail',
            [
                'name' => $this->name(),
                '`from`' => $this->from(),
                'subject' => $this->subject(),
                'body' => $this->body(),
                'sources' => implode(',', $this->sourceNames()),
                'extra_recipients' => $this->extraRecipients(),
                'data' => json_encode($this->data()->get()),
                'updated' => time(),
                'updated_by' => Session::uuid(),
            ],
            $this->id()
        )->execute();
    }

    public function setExtraRecipients(string $extra_recipients): static
    {
        $this->extra_recipients = $extra_recipients;
        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function from(): string
    {
        return $this->from;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * @return array<int,string>
     */
    public function sourceNames(): array
    {
        return explode(',', $this->sources);
    }

    public function extraRecipients(): string
    {
        return $this->extra_recipients;
    }

    public function data(): FlatArray
    {
        return $this->data_object
            ??= new FlatArray(json_decode($this->data, true, 512, JSON_THROW_ON_ERROR));
    }

    public function id(): int
    {
        return $this->id;
    }

    public static function rebuildRecipientJob(DeferredJob $job, int $id): string
    {
        $mailing = BulkMail::mailing($id);
        if (!$mailing) return "Mailing $id not found";
        $mailing->rebuildRecipients();
        return 'Rebuilt recipient list';
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

    public function category(): string
    {
        return $this->category;
    }

    /**
     * Create a copy of this mailing and return the copy. The schedule will not be retained, but other settings will be.
     */
    public function copy(): Mailing
    {
        $key = DB::query()->insertInto(
            'bulk_mail',
            [
                'name' => $this->name(),
                '`from`' => $this->from(),
                'subject' => $this->subject(),
                'body' => $this->body(),
                'sources' => implode(',', $this->sourceNames()),
                'extra_recipients' => $this->extraRecipients(),
                'category' => $this->category(),
                'created' => time(),
                'created_by' => Session::uuid(),
                'updated' => time(),
                'updated_by' => Session::uuid(),
            ]
        )->execute();
        return BulkMail::mailing($key, true);
    }

    public function setFrom(string $from): static
    {
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address');
        }
        $this->from = $from;
        return $this;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function messageCount(): int
    {
        return $this->messages()->count();
    }

    public function messages(): MessageSelect
    {
        return new MessageSelect($this);
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

    public function editUrl(): URL
    {
        return (new URL('/bulk_mail/edit:' . $this->id))
            ->setName($this->name());
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function scheduleUrl(): URL
    {
        return (new URL('/bulk_mail/schedule:' . $this->id))
            ->setName('Schedule: ' . $this->name());
    }

    /**
     * @return array<int>
     */
    public function scheduledTimes(): array
    {
        return $this->data()['schedule'] ?? [];
    }

    public function addScheduledTime(mixed $time): static
    {
        $time = Format::parseDate($time)->getTimestamp();
        $schedule = $this->scheduledTimes();
        $schedule[] = $time;
        $schedule = array_unique($schedule);
        sort($schedule);
        $this->data()->unset('schedule');
        $this->data()->set('schedule', $schedule);
        return $this;
    }

    public function removeScheduledTime(mixed $time): static
    {
        $time = Format::parseDate($time)->getTimestamp();
        $schedule = $this->scheduledTimes();
        $schedule = array_filter($schedule, function ($t) use ($time) {
            return $t != $time;
        });
        $this->data()->unset('schedule');
        $this->data()->set('schedule', $schedule);
        return $this;
    }

    public function removePastScheduledTimes(): static
    {
        $schedule = $this->scheduledTimes();
        $schedule = array_filter($schedule, function ($t) {
            return $t > time();
        });
        $this->data()->unset('schedule');
        $this->data()->set('schedule', $schedule);
        return $this;
    }

    public function scheduledSendNeeded(): bool
    {
        $schedule = $this->scheduledTimes();
        // it's always sorted, so we can just check the first one, and if it's in the past we need to send this mailing
        if (!$schedule) return false;
        $first = reset($schedule);
        return $first <= time();
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
}
