<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteInput;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use Envms\FluentPDO\Exception;
use Envms\FluentPDO\Queries\Select;
use Exception as GlobalException;

class AffiliatedNetIdAutocomplete extends AutocompleteInput
{
    const STAFF = 1;
    const VOTING_FACULTY = 2;
    const ALL_FACULTY = 4;

    public function __construct(string $id = null, int $include = 7)
    {
        parent::__construct(
            $id,
            new URL('/api/v1/unm-affiliation/person.php?include=' . $include),
            static::valueCallback(...)
        );
    }

    /**
     * 
     * @param string|null $query 
     * @param int $include 
     * @return array<int,array<string,mixed>>
     * @throws Exception 
     * @throws GlobalException 
     */
    public static function search(string|null $query, int $include): array
    {
        if (!$query) return [];
        $query = trim(strtolower($query));
        if (PersonInfo::getFullNameFor($query)) {
            $people = [$query => static::valueCallback($query)];
        } else {
            $people = [];
        }
        // name matches
        $q = static::query($include);
        foreach (preg_split('/ +/', $query) as $word) { //@phpstan-ignore-line
            $q->where(
                AbstractMappedSelect::parseJsonRefs('CONCAT(' . implode('," ",', [
                    '${data.firstname}',
                    '${data.lastname}',
                    '${data.fullname}',
                    '${data.email}',
                    'identifier',
                ]) . ')') . ' LIKE ?',
                AbstractMappedSelect::prepareLikePattern($word)
            );
        }
        foreach ($q as $result) {
            if (isset($people[$result['identifier']])) continue;
            if (count($people) >= 100) break;
            $people[$result['identifier']] = static::valueCallback($result['identifier']);
        }
        // return results
        return array_values($people);
    }

    protected static function query(int $include): Select
    {
        $cutoff = Semesters::current()->previous(3)->intVal();
        $query = SharedDB::query()
            ->from('person_info')
            ->order('updated desc')
            ->disableSmartJoin();
        if (!$include) $include = 7;
        if ($include & static::STAFF) {
            $query->where(
                AbstractMappedSelect::parseJsonRefs('${data.staff} >= ?'),
                [$cutoff]
            );
        }
        if ($include & static::ALL_FACULTY) {
            $query->where(
                AbstractMappedSelect::parseJsonRefs('${data.faculty.semester} >= ?'),
                [$cutoff]
            );
        }
        if ($include & static::VOTING_FACULTY) {
            $query->where(
                AbstractMappedSelect::parseJsonRefs('${data.faculty.voting} >= ?'),
                [$cutoff]
            );
        }
        return $query;
    }

    public static function valueCallback(string $netId): mixed
    {
        $netId = strtolower($netId);
        if (PersonInfo::getFullNameFor($netId)) {
            return [
                'html' => implode(PHP_EOL, [
                    sprintf('<div class="title">%s</div>', PersonInfo::getFullNameFor($netId)),
                    sprintf(
                        '<div class="affiliation_type">%s: %s</div>',
                        PersonInfo::getFor($netId, 'affiliation.type'),
                        PersonInfo::getFor($netId, 'affiliation.org')
                    ),
                    sprintf(
                        '<div class="affiliation_dept">%s, %s</div>',
                        PersonInfo::getFor($netId, 'affiliation.title'),
                        PersonInfo::getFor($netId, 'affiliation.department')
                    ),
                ]),
                'value' => $netId,
                'class' => 'user'
            ];
        } else {
            return [
                'html' => '<div class="title">NetID: <kbd>' . $netId . '</kbd></div>',
                'value' => $netId,
                'class' => 'user'
            ];
        }
    }
}