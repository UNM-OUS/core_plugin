<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\People;

use DigraphCMS\Config;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use DigraphCMS_Plugins\unmous\ous_digraph_module\StringFixer;
use Exception;

/**
 * Represents the information known about a single person's faculty appointment.
 */
class FacultyInfo
{
    public function __construct(
        public readonly int $id,
        public readonly string $netid,
        public readonly string $email,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly string $org,
        public readonly string $department,
        public readonly string $title,
        public readonly string $academic_title,
        public readonly string $rank,
        public readonly bool $voting,
        public readonly bool $hsc,
        public readonly bool $branch,
        public readonly bool $research,
        public readonly bool $visiting,
        public readonly string $job,
        public readonly string $time,
    ) {}

    public static function search(string $netId, bool $voting_only = false): ?FacultyInfo
    {
        $query = SharedDB::query()
            ->from('faculty_list')
            ->order('time DESC')
            ->where('netid', $netId)
            ->asObject(static::class); // @phpstan-ignore-line
        if ($voting_only) $query->where('voting');
        return $query->fetch() ?: null;
    }

    /**
     * Import a single row from a spreadsheet into the database. Much work will
     * be done to normalize and check everything.
     * @param array<string,string> $row
     * @param bool|null $voting if null, will attempt to infer from existing records
     */
    public static function import(array $row, bool|null $voting, string $job_group): void
    {
        list($first_name, $last_name) = static::importName($row);
        $netid = strtolower($row['netid']);
        $email = strtolower($row['email'] ?? $row['netid'] . '@unm.edu');
        $org = StringFixer::organization($row['org level 3 desc']);
        $department = StringFixer::department($row['org desc']);
        $title = StringFixer::jobTitle($row['job title']);
        $rank = FacultyRankParser::commonRankFromTitle($title)
            ?? FacultyRankParser::inferRankFromTitle($row['academic title'])
            ?? 'Unknown Rank';
        $voting = $voting ?? static::importVoting($row);
        $hsc = static::importHsc($org);
        $branch = static::importBranch($org);
        $research = static::importResearch($rank);
        $visiting = static::importVisiting($rank);
        // update record in main DB
        static::set(
            $netid,
            $email,
            $first_name,
            $last_name,
            $org,
            $department,
            $title,
            $rank,
            $voting,
            $hsc,
            $branch,
            $research,
            $visiting,
            $job_group
        );
        // update personinfo
        PersonInfo::setFor($netid, [
            'firstname' => $first_name,
            'lastname' => $last_name,
            'fullname' => $first_name . ' ' . $last_name,
            'email' => $email,
            'faculty' => [
                'semester' => Semesters::current()->intVal(),
                'voting' => $voting ? Semesters::current()->intVal() : null,
            ],
            'affiliation' => [
                'type' => 'faculty',
                'org' => $org,
                'department' => $department,
                'title' => $title,
                'rank' => $rank,
                'voting' => $voting,
                'hsc' => $hsc,
                'branch' => $branch,
                'research' => $research,
                'visiting' => $visiting,
            ],
        ]);
    }

    protected static function importVisiting(string $rank): bool
    {
        return str_contains($rank, 'Visiting ')
            || str_contains($rank, 'Research Scholar');
    }

    protected static function importResearch(string $rank): bool
    {
        return str_contains($rank, 'Research ');
    }

    public static function importHsc(string $org): bool
    {
        return in_array($org, Config::get('unm.hsc_orgs'));
    }

    public static function importBranch(string $org): bool
    {
        return in_array($org, Config::get('unm.branch_orgs'));
    }

    /**
     * @param array<string,string> $row
     */
    protected static function importVoting(array $row): bool
    {
        // TODO: look at row if we can get that data in the spreadsheets themselves
        // as a last resort infer from existing records
        return !!static::search(strtolower($row['netid']), true);
    }

    /**
     * @param array<string,string> $row
     * @return string[] first name, last name
     */
    public static function importName(array $row): array
    {
        $first_name = null;
        $last_name = null;
        if ($full_name = $row['full name'] ?? $row['name']) {
            if (preg_match('/^(.+?), (.+)$/', $full_name, $m)) {
                $first_name = $m[2];
                $last_name = $m[1];
            } else {
                $name = explode(' ', $full_name);
                $last_name = array_pop($name);
                $first_name = implode(' ', $name);
            }
        }
        if ($row['first name']) {
            $first_name = $row['first name'];
        }
        if ($row['last name']) {
            $last_name = $row['last name'];
        }
        // remove initials like A. B. from first name
        $first_name = preg_replace('/ [A-Z]\./', '', $first_name);
        $first_name = trim($first_name);
        $last_name = trim($last_name);
        return [$first_name, $last_name];
    }

    public static function set(
        string $netid,
        string $email,
        string $first_name,
        string $last_name,
        string $org,
        string $department,
        string $title,
        string $rank,
        bool $voting,
        bool $hsc,
        bool $branch,
        bool $research,
        bool $visiting,
        string $job_group,
    ): void {
        // normalize netid
        $netid = strtolower(trim($netid));
        if (!$netid) throw new Exception('NetID cannot be blank');
        // delete existing records from this job/netid
        SharedDB::query()
            ->delete('faculty_list')
            ->where('netid', $netid)
            ->where('job', $job_group)
            ->execute();
        // insert new record
        SharedDB::query()
            ->insertInto(
                'faculty_list',
                [
                    'netid' => $netid,
                    'email' => $email,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'org' => $org,
                    'department' => $department,
                    'title' => $title,
                    'rank' => $rank,
                    'voting' => intval($voting),
                    'hsc' => intval($hsc),
                    'branch' => intval($branch),
                    'research' => intval($research),
                    'visiting' => intval($visiting),
                    'job' => $job_group,
                    'time' => time(),
                ]
            )->execute();
    }
}
