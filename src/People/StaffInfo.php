<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\People;

use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

/**
 * Represents the information known about a single person's faculty appointment.
 */
class StaffInfo
{
    public static function search(string|null $netId): ?static
    {
        $query = SharedDB::query()
            ->from('staff')
            ->where('netid', $netId);
        if ($result = $query->fetch()) {
            return new StaffInfo(
                $result['netid'],
                $result['email'],
                $result['firstname'],
                $result['lastname'],
                $result['org'],
                $result['department'],
                $result['title']
            );
        }
        return null;
    }

    public function __construct(
        public readonly string $netId,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $org,
        public readonly string $department,
        public readonly string $title
    ) {
    }
}
