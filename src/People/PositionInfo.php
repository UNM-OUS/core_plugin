<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\People;

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
    ) {}

    public static function search(string $netid): PositionInfo
    {
        $netid = strtolower($netid);
        $faculty = FacultyInfo::search($netid);
        $staff = StaffInfo::search($netid);
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
