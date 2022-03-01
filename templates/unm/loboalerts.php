<?php

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\HTML\DIV;

// load data from webcore json file
$data = Cache::get(
    'unm/loboalerts',
    function () {
        $curl = curl_init('http://webcore.unm.edu/v2/loboalerts.json');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($curl);
        if ($resp === false) {
            throw new \Exception('UNM user source failed to load: ' . curl_error($curl));
        }
        curl_close($curl);
        if ($resp) {
            if ($data = json_decode($resp, true)) {
                if (@$data['alert'] != 'none') {
                    $data['details'] = str_replace('&#xA;', '', @$data['details'] ?? '');
                    return $data;
                }
            }
        }
        return null;
    },
    Config::get('unm.loboalert_ttl')
);

// display data if it exists
if ($data) {
    $div = (new DIV)
        ->setID('loboalert');
    $wrapper = (new DIV)
        ->setID('loboalert__content');
    if ($data['link']) {
        $wrapper->addChild('<h1><a href="' . $data['url'] . '">LoboAlert: ' . $data['alert'] . '</a></h1>');
    } else {
        $wrapper->addChild('<h1>LoboAlert: ' . $data['alert'] . '</h1>');
    }
    $wrapper->addChild('<h2>' . $data['date'] . '</h2>');
    $wrapper->addChild('<p>' . $data['details']);
    if ($data['link']) {
        $wrapper->addChild(' <a href="' . $data['url'] . '">read more</a>');
    }
    $wrapper->addChild('</p>');
    $div->addChild($wrapper);
    echo $div;
}
