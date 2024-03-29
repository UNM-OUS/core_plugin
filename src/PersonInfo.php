<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use Flatrr\FlatArray;

class PersonInfo extends FlatArray
{
    /** @var string */
    protected $identifier;

    /**
     * @param string|null $identifier
     * @param array<string,mixed> $data
     */
    public static function setFor(?string $identifier, array $data): void
    {
        if ($data && $person = static::fetch($identifier)) {
            $person->merge($data, null, true);
            $person->save();
        }
    }

    /**
     * @param string|null $identifier
     * @param string|string[] $key if an array is given, the first matching key found will be returned
     */
    public static function getFor(?string $identifier, string|array $key): mixed
    {
        if ($person = static::fetch($identifier)) {
            if (!is_array($key)) $key = [$key];
            foreach ($key as $k) {
                if ($person[$k] !== null) return $person[$k];
            }
        }
        return null;
    }

    public static function getFullNameFor(?string $identifier): ?string
    {
        if ($person = static::fetch($identifier)) return $person->fullName();
        return null;
    }

    public static function getFirstNameFor(?string $identifier): ?string
    {
        if ($person = static::fetch($identifier)) return $person->firstName();
        return null;
    }

    public static function getLastNameFor(?string $identifier): ?string
    {
        if ($person = static::fetch($identifier)) return $person->lastName();
        return null;
    }

    public static function fetch(?string $identifier): ?PersonInfo
    {
        static $cache = [];
        if (!$identifier) return null;
        return @$cache[$identifier]
            ?? $cache[$identifier] = static::doFetch($identifier);
    }

    protected static function doFetch(?string $identifier): ?PersonInfo
    {
        if (!$identifier) return null;
        $person = SharedDB::query()->from('person_info')
            ->where('identifier', $identifier)
            ->fetch();
        if ($person) return new PersonInfo($person['identifier'], json_decode($person['data'], true, 512, JSON_THROW_ON_ERROR));
        else return new PersonInfo($identifier);
    }

    /**
     * @param string|null $identifier
     * @param array<string,mixed> $data
     */
    protected function __construct(?string $identifier, array $data = [])
    {
        $this->identifier = $identifier;
        $this->set(null, $data);
    }

    public function fullName(): ?string
    {
        if ($this['fullname']) $output = $this['fullname'];
        elseif ($this['firstname'] && $this['lastname']) $output = $this['firstname'] . ' ' . $this['lastname'];
        else $output = null;
        if ($output) $output = trim($output);
        return $output ? $output : null;
    }

    public function firstName(): ?string
    {
        if ($this['firstname']) $output = $this['firstname'];
        elseif ($this['fullname']) $output = preg_replace('/ .*$/', '', $this['fullname']);
        else $output = null;
        if ($output) $output = trim($output);
        return $output ? $output : null;
    }

    public function lastName(): ?string
    {
        if ($this['lastname']) $output = $this['lastname'];
        elseif ($this['fullname']) $output = preg_replace('/^.* /', '', $this['fullname']);
        else $output = null;
        if ($output) $output = trim($output);
        return $output ? $output : null;
    }

    public function identifier(): ?string
    {
        return $this->identifier;
    }

    public function exists(): bool
    {
        return !!SharedDB::query()
            ->from('person_info')
            ->where('identifier', $this->identifier)
            ->count();
    }

    public function save(): bool
    {
        $query = SharedDB::query();
        if ($this->exists()) {
            $query = $query->update(
                'person_info',
                [
                    'data' => json_encode($this->get()),
                    'updated' => time(),
                ]
            )->where('identifier', $this->identifier);
        } else {
            $query = $query->insertInto(
                'person_info',
                [
                    'identifier' => $this->identifier,
                    'data' => json_encode($this->get()),
                    'updated' => time(),
                ]
            );
        }
        return !!$query->execute();
    }
}