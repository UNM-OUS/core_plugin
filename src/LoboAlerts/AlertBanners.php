<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Curl\CurlHelper;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\ExceptionLog;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB\SiteAlert;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB\SiteAlerts;

class AlertBanners
{
    /** @return AlertBanner[] */
    public static function alerts(): array
    {
        return Cache::get(
            'unm/loboalerts',
            function (): array {
                /** @var array<int,AlertBanner> */
                $alerts = [];
                // get alerts from main source
                $loboAlert = CurlHelper::get('https://webcore.unm.edu/v2/loboalerts.json');
                try {
                    if ($loboAlert && $loboAlert = json_decode($loboAlert, true, 512, JSON_THROW_ON_ERROR)) {
                        if ($loboAlert['alert'] != 'none') {
                            $alerts[] = new AlertBanner(
                                $loboAlert['alert'] ?? 'LoboAlert',
                                str_replace('&#xA;', '', @$loboAlert['details'] ?? ''),
                                'warning',
                                md5(serialize($loboAlert))
                            );
                        }
                    }
                } catch (\Throwable $th) {
                    ExceptionLog::log($th);
                }
                // get OUS-global alerts
                foreach (static::globalAlerts() as $alert) $alerts[] = $alert; // get site alerts
                foreach (static::siteAlerts() as $alert) $alerts[] = $alert; // use dispatcher to append more alerts
                Dispatcher::dispatchEvent('onLoboAlerts', [&$alerts]); // return the final alert
                return $alerts;
            },
            300
        );
    }

    // @phpstan-ignore-next-line we don't have a type for this yet
    public static function globalAlerts(): array
    {
        // TODO global alerts once site-level alerts are worked out
        return [];
    }

    /**
     * @return SiteAlert[]
     */
    protected static function siteAlerts(): array
    {
        // @phpstan-ignore-next-line
        return SiteAlerts::new()->current()->fetchAll();
    }
}