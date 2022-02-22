<?php

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\HTML\DIV;
use DigraphCMS\UI\Format;

// load data from webcore json file
$data = Cache::get(
    'unm/loboalerts',
    function () {
        if ($file = file_get_contents('http://webcore.unm.edu/v2/loboalerts.json')) {
            if ($data = json_decode($file, true)) {
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
    $div = (new DIV())
        ->setID('lobalert');
    $wrapper = (new DIV())
        ->addClass('loboalert__wrapper');
    if ($data['link']) {
        $wrapper->addChild('<h1><a href="' . $data['url'] . '">LoboAlert: ' . $data['alert'] . '</a></h1>');
    } else {
        $wrapper->addChild('<h1>LoboAlert' . $data['alert'] . '</h1>');
    }
    $wrapper->addChild('<h2>' . Format::date($data['date']) . '</h2>');
    $wrapper->addChild('<p>' . $data['details']);
    if ($data['link']) {
        $wrapper->addChild(' <a href="' . $data['url'] . '">read more</a>');
    }
    $wrapper->addChild('</p>');
    echo $div;
}