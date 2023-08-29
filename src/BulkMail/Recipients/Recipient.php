<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class Recipient
{
    /** @var string */
    protected $email;
    /** @var string|null */
    protected $user_id;
    /** @var User|null */
    protected $user;

    /**
     * @param string $email
     * @param User|string|null $user
     */
    public function __construct(string $email, mixed $user = null)
    {
        $this->email = strtolower(trim($email));
        if ($user instanceof User) {
            $this->user_id = $user->uuid();
            $this->user = $user;
        } else {
            $this->user_id = $user;
            $this->user = null;
        }
    }

    public function email(): string
    {
        return $this->email;
    }

    public function userUuid(): ?string
    {
        return $this->user_id;
    }

    public function user(): ?User
    {
        if (!$this->user_id) return null;
        return $this->user
            ?? ($this->user = Users::get($this->user_id));
    }
}
