<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use Flatrr\FlatArray;

class PersonInfo extends FlatArray
{
    protected $netid;
    protected static $personCache = [];

    public static function setFor(?string $netid, array $data)
    {
        if ($data && $person = static::forNetID($netid)) {
            $person->set(null, $data);
            $person->save();
        }
    }

    public static function getFor(?string $netid, $key)
    {
        if ($person = static::forNetID($netid)) {
            if (!is_array($key)) $key = [$key];
            foreach ($key as $k) {
                if ($person[$k] !== null) return $person[$k];
            }
        }
        return null;
    }

    public static function getFullNameFor(?string $netid): ?string
    {
        if ($person = static::forNetID($netid)) return $person->fullName();
        return null;
    }

    public static function getFirstNameFor(?string $netid): ?string
    {
        if ($person = static::forNetID($netid)) return $person->firstName();
        return null;
    }

    public static function getLastNameFor(?string $netid): ?string
    {
        if ($person = static::forNetID($netid)) return $person->lastName();
        return null;
    }

    public static function forNetID(?string $netid): ?PersonInfo
    {
        if (!isset(static::$personCache[$netid])) static::$personCache[$netid] = static::doGetForNetID($netid);
        return static::$personCache[$netid];
    }

    protected static function doGetForNetID(?string $netid): ?PersonInfo
    {
        if (!$netid) return null;
        $person = SharedDB::query()->from('person_info')
            ->where('netid', $netid)
            ->fetch();
        if ($person) return new PersonInfo($person['netid'], json_decode($person['data'], true));
        else return new PersonInfo($netid);
    }

    protected function __construct(?string $netid, array $data = [])
    {
        $this->netid = $netid;
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

    public function netID(): ?string
    {
        return $this->netid;
    }

    public function exists(): bool
    {
        return !!SharedDB::query()
            ->from('person_info')
            ->where('netid', $this->netid)
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
            )->where('netid', $this->netid);
        } else {
            $query = $query->insertInto(
                'person_info',
                [
                    'netid' => $this->netid,
                    'data' => json_encode($this->get())
                ]
            );
        }
        return !!$query->execute();
    }
}
