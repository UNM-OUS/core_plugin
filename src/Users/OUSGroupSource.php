<?php
namespace Digraph\Modules\ous_digraph_module\Users;

use Digraph\Users\GroupSources\AbstractGroupSource;

class OUSGroupSource extends AbstractGroupSource
{
    public function groups(string $id) : ?array
    {
        //if ID isn't a NetID, short circuit, this source knows nothing
        $id = explode('@', $id);
        $provider = array_pop($id);
        if ($provider != 'netid') {
            return [];
        }
        $netid = array_shift($id);
        //check cache
        $cache = $this->cms->cache();
        $cacheID = md5(static::class).'.'.$netid;
        $groups = $cache->getItem($cacheID);
        //load and save into cache if cache isn't hit
        if (!$groups->isHit()) {
            $url = 'https://ousadmin.unm.edu/groups/?netid='.$netid;
            if ($data = json_decode(file_get_contents($url))) {
                $groups->set($data);
                $cache->save($groups);
            }
        }
        //return
        $groups = $groups->get();
        return $groups?$groups:[];
    }
}
