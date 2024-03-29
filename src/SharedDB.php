<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\Config;
use DigraphCMS\DB\SqliteShim;
use Envms\FluentPDO\Query;
use PDO;

abstract class SharedDB
{
    public static function pdo(): PDO
    {
        static $pdo;
        if (!$pdo) {
            $pdo = new PDO(
                Config::get('unm.shared_db.dsn') ?? static::localDSN(),
                Config::get('unm.shared_db.user'),
                Config::get('unm.shared_db.pass')
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite') {
                if (Config::get('db.sqlite.create_functions')) {
                    SqliteShim::createFunctions($pdo);
                }
            }
        }
        return $pdo;
    }

    protected static function localDSN(): string
    {
        $file = realpath(__DIR__ . '/../shared.sqlite');
        return "sqlite:$file";
    }

    public static function query(): Query
    {
        return new Query(static::pdo());
    }
}
