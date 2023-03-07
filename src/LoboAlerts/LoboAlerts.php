<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Curl\CurlHelper;

class LoboAlerts
{
    public static function alerts(): array
    {
        return Cache::get(
            'unm/loboalerts',
            function (): array {
                /** @var array<int,LoboAlert> */
                $alerts = [];
                // get alerts from main source
                $loboAlert = CurlHelper::get('https://webcore.unm.edu/v2/loboalerts.json');
                if ($loboAlert && $loboAlert = json_decode($loboAlert, true, 512, JSON_THROW_ON_ERROR)) {
                    if ($loboAlert['alert'] != 'none') {
                        $alerts[] = new LoboAlert(
                            $loboAlert['alert'] ?? 'LoboAlert',
                            str_replace('&#xA;', '', @$loboAlert['details'] ?? ''),
                            'warning',
                            md5(serialize($loboAlert))
                        );
                    }
                }
                // get COVID alert, either from OUS site or from main site
                $covidBanner = CurlHelper::get('https://www.unm.edu/includes/temp-alert.html');
                if ($covidBanner) {
                    $covidBanner = trim(preg_replace('/<!-- [^\-]+? -->/', '', $covidBanner));
                    do {
                        $previous = $covidBanner;
                        $covidBanner = trim(preg_replace('/^<div[^>]*>(.+)<\/div>$/is', '$1', $covidBanner));
                    } while ($previous != $covidBanner);
                    if ($covidBanner = LoboAlert::parse($covidBanner, 'covid')) {
                        $alerts[] = $covidBanner;
                    }
                }
                return $alerts;
            },
            60
        );
    }
}
