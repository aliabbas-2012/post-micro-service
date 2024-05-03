<?php

$envVar = app()['env'];
$prefix = "";
if ($envVar == "testing") {
    $prefix = "TEST_";
}
return [
    /*
      |--------------------------------------------------------------------------
      | PDO Fetch Style
      |--------------------------------------------------------------------------
      |
      | By default, database results will be returned as instances of the PHP
      | stdClass object; however, you may desire to retrieve records in an
      | array format for simplicity. Here you can tweak the fetch style.
      |
     */

    'fetch' => PDO::FETCH_CLASS,
    /*
      |--------------------------------------------------------------------------
      | Default Database Connection Name
      |--------------------------------------------------------------------------
      |
      | Here you may specify which of the database connections below you wish
      | to use as your default connection for all database work. Of course
      | you may use many connections at once using the Database library.
      |
     */
    'default' => env('DB_CONNECTION', 'mysql'),
    /*
      |--------------------------------------------------------------------------
      | Database Connections
      |--------------------------------------------------------------------------
      |
      | Here are each of the database connections setup for your application.
      | Of course, examples of configuring each database platform that is
      | supported by Laravel is shown below to make development simple.
      |
      |
      | All database work in Laravel is done through the PHP PDO facilities
      | so make sure you have the driver for your particular database of
      | choice installed on your machine before you begin development.
      |
     */

//    $dbhost = $_SERVER['RDS_HOSTNAME'];
//$dbport = $_SERVER['RDS_PORT'];
//$dbname = $_SERVER['RDS_DB_NAME'];
//$charset = 'utf8' ;
//
//$dsn = "mysql:host={$dbhost};port={$dbport};dbname={$dbname};charset={$charset}";
//$username = $_SERVER['RDS_USERNAME'];
//$password = $_SERVER['RDS_PASSWORD'];
//
//$pdo = new PDO($dsn, $username, $password);
    'connections' => [
        'testing' => [
            'driver' => 'mysql',
            'host' => env('DB_TEST_HOST', 'localhost'),
            'database' => env('DB_TEST_DATABASE', 'restapi_test'),
            'username' => env('DB_TEST_USERNAME', ''),
            'password' => env('DB_TEST_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
            'prefix' => env('DB_PREFIX', ''),
            'timezone' => env('DB_TIMEZONE', '+00:00'),
            'strict' => env('DB_STRICT_MODE', false),
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', base_path('database/database.sqlite')),
            'prefix' => env('DB_PREFIX', ''),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST'),
            'port' => env($prefix . 'DB_PORT'),
            'database' => env($prefix . 'DB_NAME'),
            'username' => env($prefix . 'DB_USER'),
            'password' => env($prefix . 'DB_PASS'),
//            'charset' => env('DB_CHARSET', 'utf8'),
//            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_general_ci'),
            'prefix' => '',
            'timezone' => env('DB_TIMEZONE', '+00:00'),
            'strict' => env('DB_STRICT_MODE', false),
        ],
        'prod_mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST'),
            'port' => env($prefix . 'DB_PORT'),
            'database' => "pro_fayvo_db_latest",
            'username' => env($prefix . 'DB_USER'),
            'password' => env($prefix . 'DB_PASS'),
//            'charset' => env('DB_CHARSET', 'utf8'),
//            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_general_ci'),
            'prefix' => '',
            'timezone' => env('DB_TIMEZONE', '+00:00'),
            'strict' => env('DB_STRICT_MODE', false),
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => env('DB_PREFIX', ''),
            'schema' => env('DB_SCHEMA', 'public'),
        ],
        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST', 'localhost'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => env('DB_PREFIX', ''),
        ],
        'stats_db' => [
            'driver' => 'mysql',
            'host' => env('STATS_DB_HOST'),
            'port' => env('STATS_DB_PORT'),
            'database' => env('STATS_DB_NAME'),
            'username' => env('STATS_DB_USER'),
            'password' => env('STATS_DB_PASS'),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_general_ci'),
            'prefix' => '',
            'timezone' => env('DB_TIMEZONE', '+00:00'),
            'strict' => env('DB_STRICT_MODE', false),
        ],
        'ads_pgsql' => [
            'driver' => 'pgsql',
            'host' => env('PG_DB_HOST', 'localhost'),
            'port' => env('PG_DB_PORT', 5432),
            'database' => 'fayvo_ads_db',
            'username' => env('PG_DB_ADS_USERNAME', 'forge'),
            'password' => env('PG_DB_ADS_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => env('DB_PREFIX', ''),
            'schema' => env('DB_SCHEMA', 'public'),
        ],
    ],
    /*
      |--------------------------------------------------------------------------
      | Migration Repository Table
      |--------------------------------------------------------------------------
      |
      | This table keeps track of all the migrations that have already run for
      | your application. Using this information, we can determine which of
      | the migrations on disk haven't actually been run in the database.
      |
     */
    'migrations' => 'migrations',
    /*
      |--------------------------------------------------------------------------
      | Redis Databases
      |--------------------------------------------------------------------------
      |
      | Redis is an open source, fast, and advanced key-value store that also
      | provides a richer set of commands than a typical key-value systems
      | such as APC or Memcached. Laravel makes it easy to dig right in.
      |
     */
    'redis' => [
        'cluster' => env('REDIS_CLUSTER', false),
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 0),
            'password' => env('REDIS_PASSWORD', null),
        ],
        'content_data' => [
            'host' => env('REDIS_HOST'),
            'port' => env('REDIS_PORT'),
            'database' => 2,
        ],
        'user_data' => [
            'host' => env('REDIS_HOST'),
            'port' => env('REDIS_PORT'),
            'database' => 3,
        ],
        'post_media' => [
            'host' => env('REDIS_HOST'),
            'port' => env('REDIS_PORT'),
            'database' => 6,
        ],
        'friend_list' => [
            'host' => env('REDIS_HOST'),
            'port' => env('REDIS_PORT'),
            'database' => 7,
        ],
    ],
];
