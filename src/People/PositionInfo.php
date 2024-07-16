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
        public readonly bool $faculty,
        public readonly bool $votingFaculty,
        public readonly bool $staff,
        public readonly string|null $title,
        public readonly string|null $department,
        public readonly string|null $org,
        public readonly string|null $facultyRank,
        public readonly string|null $facultyAcademicTitle,
        public readonly bool $facultyResearch,
        public readonly bool $facultyVisiting,
        public readonly bool $clinicianEducator,
    ) {
    }

    public static function search(string $netid): PositionInfo
    {
        $netid = strtolower($netid);
        $faculty = FacultyInfo::search($netid);
        $staff = StaffInfo::search($netid);
        return new PositionInfo(
            $netid,
            $faculty ? true : false,
            $faculty ? $faculty->voting : false,
            $staff ? true : false,
            $faculty?->title ?: $faculty?->academicTitle ?: $staff?->title ?: null,
            $faculty?->department ?: $staff?->department ?: null,
            $faculty?->org ?: $staff?->org ?: null,
            $faculty?->rank() ?: null,
            $faculty?->academicTitle ?: null,
            $faculty?->isResearchFaculty() ?: false,
            $faculty?->isVisiting() ?: false,
            $faculty?->isClinicianEducator() ?: false
        );
    }
}
