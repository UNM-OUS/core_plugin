<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail;

use DateTime;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class Message
{
    /** @var int */
    protected $id;
    /** @var int */
    protected $bulk_mail_id;
    /** @var string */
    protected $email;
    /** @var string|null */
    protected $user;
    /** @var int|null */
    protected $sent;
    /** @var string|null */
    protected $email_uuid;

    public function emailMessage(): ?Email
    {
        if (!$this->emailUuid()) return null;
        return Emails::get($this->emailUuid());
    }

    public function emailUuid(): ?string
    {
        return $this->email_uuid;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function user(): ?User
    {
        if (!$this->user) return null;
        return Users::get($this->user);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function mailing(): Mailing
    {
        return BulkMail::mailing($this->bulk_mail_id);
    }

    public function sent(): ?DateTime
    {
        if (!$this->sent) return null;
        return (new DateTime)->setTimestamp($this->sent);
    }
}
