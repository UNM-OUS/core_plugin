<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\LoboAlerts;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Curl\CurlHelper;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\ExceptionLog;

class LoboAlerts
{
    /** @return LoboAlert[] */
    public static function alerts(): array
    {
        return Cache::get(
            'unm/loboalerts',
            function (): array {
                /** @var array<int,LoboAlert> */
                $alerts = [];
                // get alerts from main source
                $loboAlert = CurlHelper::get('https://webcore.unm.edu/v2/loboalerts.json');
                try {
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
                } catch (\Throwable $th) {
                    ExceptionLog::log($th);
                }
                // use dispatcher to append more alerts
                Dispatcher::dispatchEvent('onLoboAlerts', [&$alerts]);
                return $alerts;
            },
            300
        );
    }
}