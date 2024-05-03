<?php

//test script needs to be removed
if (php_sapi_name() == "cli") {
    $database = config('database.connections.prod_mysql');
    // chnage database connection dynamically
    print_r($database);
    config(['database.connections.mysql' => $database]);
}