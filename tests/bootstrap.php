<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

use Cycle\Database\Config;
use Cycle\Schema\Tests\BaseTest;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');

//Composer
require dirname(__DIR__) . '/vendor/autoload.php';

$drivers = [
    'sqlite' => new Config\SQLiteDriverConfig(
        queryCache: true,
    ),
    'mysql' => new Config\MySQLDriverConfig(
        connection: new Config\MySQL\TcpConnectionConfig(
            database: 'spiral',
            host: getenv('TEST_ENV') === 'local' ? 'mysql_latest' : '127.0.0.1',
            port: getenv('TEST_ENV') === 'local' ? 3306 : 13306,
            user: 'root',
            password: 'root',
        ),
        queryCache: true
    ),
    'postgres' => new Config\PostgresDriverConfig(
        connection: new Config\Postgres\TcpConnectionConfig(
            database: 'spiral',
            host: getenv('TEST_ENV') === 'local' ? 'postgres' : '127.0.0.1',
            port: getenv('TEST_ENV') === 'local' ? 5432 : 15432,
            user: 'postgres',
            password: 'postgres',
        ),
        schema: 'public',
        queryCache: true,
    ),
    'sqlserver' => new Config\SQLServerDriverConfig(
        connection: new Config\SQLServer\TcpConnectionConfig(
            database: 'tempdb',
            host: getenv('TEST_ENV') === 'local' ? 'sqlserver' : '127.0.0.1',
            port: getenv('TEST_ENV') === 'local' ? 1433 : 11433,
            user: 'SA',
            password: 'SSpaSS__1'
        ),
        queryCache: true
    ),
];

$db = getenv('DB') ?: null;
BaseTest::$config = [
        'debug' => false,
        'strict' => true,
        'benchmark' => true,
    ] + (
    $db === null
        ? $drivers
        : array_intersect_key($drivers, array_flip((array)$db))
    );
