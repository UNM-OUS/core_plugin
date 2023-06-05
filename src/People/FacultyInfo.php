<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\People;

/**
 * Represents the information known about a single person's faculty appointment.
 */
class FacultyInfo
{
    public static function search(string|null $netId, bool $voting_only = false): ?static
    {
        $query = $voting_only ? VotingFaculty::select() : AllFaculty::select();
        $query->where('netid', $netId);
        if ($result = $query->fetch()) {
            return new FacultyInfo(
                $result['netid'],
                $result['email'],
                $result['firstname'],
                $result['lastname'],
                $result['org'],
                $result['department'],
                $result['title'],
                $voting_only
                    ? true
                    : boolval(VotingFaculty::select()->where('netid', $netId)->count())
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
        public readonly string $title,
        public readonly bool $voting
    ) {
    }
}
