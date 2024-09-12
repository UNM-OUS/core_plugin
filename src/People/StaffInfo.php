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
    public readonly int $id; // @phpstan-ignore-line comes from DB
    public readonly string $netid; // @phpstan-ignore-line comes from DB
    public readonly string $email; // @phpstan-ignore-line comes from DB
    public readonly string $first_name; // @phpstan-ignore-line comes from DB
    public readonly string $last_name; // @phpstan-ignore-line comes from DB
    public readonly string $org; // @phpstan-ignore-line comes from DB
    public readonly string $department; // @phpstan-ignore-line comes from DB
    public readonly string $title; // @phpstan-ignore-line comes from DB
    public readonly bool $hsc; // @phpstan-ignore-line comes from DB
    public readonly bool $branch; // @phpstan-ignore-line comes from DB
    public readonly string $job; // @phpstan-ignore-line comes from DB
    public readonly string $time; // @phpstan-ignore-line comes from DB

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
        $netid = trim(strtolower($row['netid']));
        if (!$netid) throw new Exception('NetID cannot be blank');
        $existing = static::search($netid);
        // email address
        $email = ($row['email'] ? $row['email'] : null)
            ?? $existing?->email
            ?? $netid . '@unm.edu';
        // org (org level 3 desc in banner)
        $org = ($row['org level 3 desc'] ? $row['org level 3 desc'] : null)
            ?? $existing?->org
            ?? 'Unknown Organization';
        $org = StringFixer::organization($org);
        // department (org desc in banner)
        $department = ($row['org desc'] ? $row['org desc'] : null)
            ?? $existing?->department
            ?? 'Unknown Department';
        $department = StringFixer::department($department);
        // job title
        $title = ($row['job title'] ? $row['job title'] : null)
            ?? $existing?->title
            ?? 'Unknown Title';
        $title = StringFixer::jobTitle($title);
        // flags
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
        // delete old records for this person
        SharedDB::query()
            ->delete('staff_list')
            ->where('netid', $netid)
            ->where('job <> ?', $job_group)
            ->execute();
        // update personinfo
        $first_name = PersonInfo::getFirstNameFor($netid) ?? $first_name;
        $last_name = PersonInfo::getLastNameFor($netid) ?? $last_name;
        $full_name = PersonInfo::getFullNameFor($netid) ?? $first_name . ' ' . $last_name;
        PersonInfo::setFor($netid, [
            'firstname' => $first_name,
            'lastname' => $last_name,
            'fullname' => $full_name,
            'email' => $email,
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
