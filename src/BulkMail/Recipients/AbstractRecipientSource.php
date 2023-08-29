<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\Recipients;

/**
 * Extend this class to provide a list of recipients for a mailing. Each
 * recipient includes an email and optionally a User.
 */
abstract class AbstractRecipientSource
{
    /** 
     * Retrieve an iterable/array list of recipients from this source
     * 
     * @return iterable<mixed,Recipient> 
     */
    abstract public function recipients(): iterable;

    /**
     * Return a best guess for the current number of recipients in this source.
     * It is acceptable for this number to be approximate.
     *
     * @return integer
     */
    abstract public function count(): int;

    /**
     * A human-friendly label to use for identifying this source in the admin
     * interfaces.
     *
     * @return string
     */
    abstract public function label(): string;

    /** @var string */
    protected $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }
}
