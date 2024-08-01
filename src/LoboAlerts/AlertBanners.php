<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\Curl\CurlHelper;
use DigraphCMS\DB\DB;
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
                // add test mode alert if configured
                if (Config::get('unm.test_site.active')) {
                    $alerts[] = new AlertBanner(
                        Config::get('unm.test_site.banner.title')
                            ?: 'This is a test site',
                        Config::get('unm.test_site.banner.message')
                            ?: 'This is a site intended for internal testing and development. It may not be accurate or up-to-date.',
                        'test-site',
                        'testmode',
                    );
                }
                // also check if we're using test databases
                if (str_ends_with(Config::get('db.name'),'_test')) {
                    $alerts[] = new AlertBanner(
                        'Test database in use',
                        'This site is currently using a test database. Changes made here will not affect the live site, and will be periodically discarded.',
                        'test-site',
                        'testdb',
                    );
                }
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
