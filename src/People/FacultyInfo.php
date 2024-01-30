<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\People;

use DigraphCMS\Config;
use DigraphCMS\ExceptionLog;
use Exception;

/**
 * Represents the information known about a single person's faculty appointment.
 */
class FacultyInfo
{
    protected string|bool $rank = false;

    public static function search(string|null $netId, bool $voting_only = false): ?FacultyInfo
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
                $result['academic_title'],
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
        public readonly string $academicTitle,
        public readonly bool $voting
    ) {
    }

    public function hsc(): bool
    {
        return in_array($this->org, Config::get('unm.hsc_orgs'));
    }

    public function branch(): bool
    {
        return in_array($this->org, Config::get('unm.branch_orgs'));
    }

    public function north(): bool
    {
        return $this->hsc() || in_array($this->org, Config::get('unm.north_orgs'));
    }

    public function rank(): string
    {
        if (!is_string($this->rank)) {
            $this->rank =
                FacultyRanks::commonRankFromTitle($this->title)
                ?? FacultyRanks::inferRankFromTitle($this->academicTitle)
                ?? false;
            if (!$this->rank) {
                ExceptionLog::log(new Exception('Couldn\'t parse faculty rank'));
                $this->rank = 'Unknown Rank';
            }
        }
        return $this->rank;
    }

    public function isClinicianEducator(): bool
    {
        return str_contains($this->rank(), 'Clinician Educator');
    }

    public function isResearchFaculty(): bool
    {
        return str_contains($this->rank(), 'Research ');
    }

    public function isVisiting(): bool
    {
        return str_contains($this->rank(), 'Visiting ')
            || str_contains($this->rank(), 'Research Scholar');
    }
}