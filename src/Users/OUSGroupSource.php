<?php

namespace Digraph\Modules\ous_digraph_module\Users;

use Digraph\Users\GroupSources\AbstractGroupSource;

class OUSGroupSource extends AbstractGroupSource
{
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
        $cache = $this->cms->cache();
        $cacheID = md5(static::class) . '.' . $netid;
        $groups = $cache->getItem($cacheID);
        //load and save into cache if cache isn't hit
        if (!$groups->isHit()) {
            $url = 'https://secretary.unm.edu/groups/index.php?netid=' . $netid;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if ($data = json_decode(curl_exec($ch), true)) {
                $groups->set($data);
                $groups->expiresAfter(3600);
                $cache->save($groups);
            }
            curl_close($ch);
        }
        //return
        $groups = $groups->get();
        return $groups ? $groups : [];
    }
}
