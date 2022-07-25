<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use Flatrr\FlatArray;

class PersonInfo extends FlatArray
{
    protected $identifier;
    protected static $personCache = [];

    public static function setFor(?string $identifier, array $data)
    {
        if ($data && $person = static::fetch($identifier)) {
            $person->set(null, $data);
            $person->save();
        }
    }

    public static function getFor(?string $identifier, $key)
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
        if (!$identifier) return null;
        if (!isset(static::$personCache[$identifier])) static::$personCache[$identifier] = static::doFetch($identifier);
        return @static::$personCache[$identifier];
    }

    protected static function doFetch(?string $identifier): ?PersonInfo
    {
        if (!$identifier) return null;
        $person = SharedDB::query()->from('person_info')
            ->where('identifier', $identifier)
            ->fetch();
        if ($person) return new PersonInfo($person['identifier'], json_decode($person['data'], true));
        else return new PersonInfo($identifier);
    }

    protected function __construct(?string $identifier, array $data = [])
    {
        $this->identifier = $identifier;
        $this->set(null, $data);
    }

    public function fullName(): ?string
    {
        if ($this['fullname']) return $this['fullname'];
        elseif ($this['firstname'] && $this['lastname']) return $this['firstname'] . ' ' . $this['lastname'];
        else return null;
    }

    public function firstName(): ?string
    {
        if ($this['firstname']) return $this['firstname'];
        elseif ($this['fullname']) return preg_replace('/ .*$/', '', $this['fullname']);
        else return null;
    }

    public function lastName(): ?string
    {
        if ($this['lastname']) return $this['lastname'];
        elseif ($this['fullname']) return preg_replace('/^.* /', '', $this['fullname']);
        else return null;
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
                    'data' => json_encode($this->get())
                ]
            )->where('identifier', $this->identifier);
        } else {
            $query = $query->insertInto(
                'person_info',
                [
                    'identifier' => $this->identifier,
                    'data' => json_encode($this->get())
                ]
            );
        }
        return !!$query->execute();
    }
}
