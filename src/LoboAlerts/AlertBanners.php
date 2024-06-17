<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Curl\CurlHelper;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\ExceptionLog;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB\GlobalAlerts;
use DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts\DB\GlobalAlert;
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
                foreach (static::globalAlerts() as $alert) $alerts[] = $alert;
                // get site alerts
                foreach (static::siteAlerts() as $alert) $alerts[] = $alert;
                // use dispatcher to append more alerts
                Dispatcher::dispatchEvent('onLoboAlerts', [&$alerts]);
                // return the final alert list
                return $alerts;
            },
            300
        );
    }

    /**
     * @return GlobalAlert[]
     */
    public static function globalAlerts(): array
    {
        // @phpstan-ignore-next-line
        return GlobalAlerts::new()->currentAlerts()->fetchAll();
    }

    /**
     * @return SiteAlert[]
     */
    protected static function siteAlerts(): array
    {
        // @phpstan-ignore-next-line
        return SiteAlerts::new()->currentAlerts()->fetchAll();
    }
}
