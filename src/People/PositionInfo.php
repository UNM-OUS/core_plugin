<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\People;

use DigraphCMS\Users\User;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OUS;

/**
 * Class to look up information about a person's current position at UNM, either
 * faculty or staff.
 */
class PositionInfo
{
    public function __construct(
        public readonly string $netid,
        public readonly string $email,
        public readonly bool $faculty,
        public readonly bool $votingFaculty,
        public readonly bool $staff,
        public readonly string|null $title,
        public readonly string|null $department,
        public readonly string|null $org,
        public readonly string|null $facultyRank,
        public readonly bool $facultyResearch,
        public readonly bool $facultyVisiting,
        public readonly bool $branch,
        public readonly bool $hsc,
    )
    {
    }

    /**
     * Get all positions for all NetIDs and emails of the given user.
     *
     * @param User $user
     *
     * @return array<PositionInfo>
     */
    public static function searchUser(User $user): array
    {
        $results = [];
        // search by NetID
        $netIDs = OUS::userNetIDs($user);
        foreach ($netIDs as $netID) {
            $result = static::search($netID);
            if ($result) {
                $results[serialize([$result->netid, $result->staff, $result->faculty])] = $result;
            }
        }
        // search by email
        $emails = $user->emails();
        foreach ($emails as $email) {
            $result = static::searchByEmail($email);
            if ($result) {
                $results[serialize([$result->netid, $result->staff, $result->faculty])] = $result;
            }
        }
        // return results
        return array_values($results);
    }

    public static function search(string $netid): PositionInfo|null
    {
        $netid = strtolower($netid);
        $faculty = FacultyInfo::search($netid);
        $staff = StaffInfo::search($netid);
        if (!$faculty && !$staff)
            return null;
        return new PositionInfo(
            netid: $netid,
            email: $faculty?->email ?: $staff?->email ?: "$netid@unm.edu",
            faculty: $faculty ? true : false,
            votingFaculty: $faculty ? $faculty->voting : false,
            staff: $staff ? true : false,
            title: $faculty?->title ?: $staff?->title ?: null,
            department: $faculty?->department ?: $staff?->department ?: null,
            org: $faculty?->org ?: $staff?->org ?: null,
            facultyRank: $faculty?->rank ?: null,
            facultyResearch: $faculty?->research ?: false,
            facultyVisiting: $faculty?->visiting ?: false,
            branch: ($faculty?->branch || $staff?->branch),
            hsc: ($faculty?->hsc || $staff?->hsc),
        );
    }

    public static function searchByEmail(string $email): PositionInfo|null
    {
        $email = strtolower($email);
        $faculty = FacultyInfo::query()->where('email', $email)->fetch();
        $staff = StaffInfo::query()->where('email', $email)->fetch();
        if (!$faculty && !$staff) return null;
        return new PositionInfo(
            netid: $faculty?->netid ?: $staff?->netid,
            email: $email,
            faculty: $faculty ? true : false,
            votingFaculty: $faculty ? $faculty->voting : false,
            staff: $staff ? true : false,
            title: $faculty?->title ?: $staff?->title ?: null,
            department: $faculty?->department ?: $staff?->department ?: null,
            org: $faculty?->org ?: $staff?->org ?: null,
            facultyRank: $faculty?->rank ?: null,
            facultyResearch: $faculty?->research ?: false,
            facultyVisiting: $faculty?->visiting ?: false,
            branch: $faculty?->branch || $staff?->branch,
            hsc: $faculty?->hsc || $staff?->hsc,
        );
    }
}
