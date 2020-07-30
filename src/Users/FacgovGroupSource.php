<?php
namespace Digraph\Modules\ous_digraph_module\Users;

use Digraph\CMS;
use Digraph\Users\GroupSources\AbstractGroupSource;

class FacgovGroupSource extends AbstractGroupSource
{
    protected $prefix = 'comm_';
    protected $source = null;

    const DISALLOWED_GROUPS = [
        'root',
        'webmaster',
        'editor',
        'FERPA',
        'officeStaff',
    ];

    public function __construct(CMS $cms, $extra = null)
    {
        parent::__construct($cms);
        $this->prefix = $extra['prefix'];
        $this->source = $extra['source'];
    }

    public function members()
    {
        //check cache
        $cache = $this->cms->cache();
        $cacheID = md5(static::class . '.' . $this->source);
        $roster = $cache->getItem($cacheID);
        //load and save into cache if cache isn't hit
        if (!$roster->isHit()) {
            $url = $this->source;
            if ($data = json_decode(file_get_contents($url), true)) {
                $roster->set($data);
                $cache->save($roster);
            }
        }
        //return
        $roster = $roster->get();
        return $roster;
    }

    public function groups(string $id): ?array
    {
        //if ID isn't a NetID, short circuit, this source knows nothing
        $id = explode('@', $id);
        $provider = array_pop($id);
        if ($provider != 'netid') {
            return [];
        }
        $netid = array_shift($id);
        //check cache
        foreach ($this->members() as $member) {
            if ($member['netid'] && $member['netid'] == $netid) {
                $groups = [$this->prefix . 'member'];
                foreach ($member['positions'] as $pos) {
                    $pos = preg_replace('/[^a-zA-Z0-9_]/', '_', $pos);
                    $pos = $this->prefix . $pos;
                    if (!in_array($pos, static::DISALLOWED_GROUPS)) {
                        $groups[] = $pos;
                    }
                }
                return $groups;
            }
        }
        return [];
    }
}
