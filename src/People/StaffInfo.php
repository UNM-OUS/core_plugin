<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\People;

use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use DigraphCMS_Plugins\unmous\ous_digraph_module\StringFixer;
use Exception;

/**
 * Represents the information known about a single person's faculty appointment.
 */
class StaffInfo
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
        public readonly bool $hsc,
        public readonly bool $branch,
        public readonly string $job,
        public readonly string $time,
    ) {}

    public static function search(string $netId): ?StaffInfo
    {
        $query = SharedDB::query()
            ->from('staff_list')
            ->where('netid', $netId)
            ->asObject(static::class); // @phpstan-ignore-line
        return $query->fetch() ?: null;
    }

    /**
     * Import a single row from a spreadsheet into the database. Much work will
     * be done to normalize and check everything.
     * @param array<string,string> $row
     */
    public static function import(array $row, string $job_group): void
    {
        list($first_name, $last_name) = FacultyInfo::importName($row);
        $netid = strtolower($row['netid']);
        $email = strtolower($row['email'] ?? $row['netid'] . '@unm.edu');
        $org = StringFixer::organization($row['org level 3 desc']);
        $department = StringFixer::department($row['org desc']);
        $title = StringFixer::jobTitle($row['job title']);
        $hsc = FacultyInfo::importHsc($org);
        $branch = FacultyInfo::importBranch($org);
        // update record in main DB
        static::set(
            $netid,
            $email,
            $first_name,
            $last_name,
            $org,
            $department,
            $title,
            $hsc,
            $branch,
            $job_group,
        );
        // update personinfo
        PersonInfo::setFor($netid, [
            'firstname' => PersonInfo::getFirstNameFor($netid) ?? $first_name,
            'lastname' => PersonInfo::getLastNameFor($netid) ?? $last_name,
            'email' => PersonInfo::getFor($netid, 'email') ?? $email,
            'staff' => Semesters::current()->intVal(),
            'affiliation' => [
                'type' => 'Staff',
                'org' => $org,
                'department' => $department,
                'title' => $title,
                'hsc' => $hsc,
                'branch' => $branch,
            ],
        ]);
    }

    public static function set(
        string $netid,
        string $email,
        string $first_name,
        string $last_name,
        string $org,
        string $department,
        string $title,
        bool $hsc,
        bool $branch,
        string $job_group,
    ): void {
        // normalize netid
        $netid = strtolower(trim($netid));
        if (!$netid) throw new Exception('NetID cannot be blank');
        // delete existing records from this job/netid
        SharedDB::query()
            ->delete('staff_list')
            ->where('netid', $netid)
            ->where('job', $job_group)
            ->execute();
        // insert new record
        SharedDB::query()
            ->insertInto(
                'staff_list',
                [
                    'netid' => $netid,
                    'email' => $email,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'org' => $org,
                    'department' => $department,
                    'title' => $title,
                    'hsc' => intval($hsc),
                    'branch' => intval($branch),
                    'job' => $job_group,
                    'time' => time(),
                ]
            )->execute();
    }
}
