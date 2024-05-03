<?php

/**
 * This file will pick current environment with respect to our gateways
 */
$envGateways = [
    "vg4fo3x5jl" => "staging",
    "9abyinm5oa" => "production",
    "lorb8s58n0" => "staging",
    "rvjuh4rga8" => "production",
];
$currentGateway = !empty($_SERVER["HTTP_X_AMZN_APIGATEWAY_API_ID"]) ? $_SERVER["HTTP_X_AMZN_APIGATEWAY_API_ID"] : "";

if (!empty($currentGateway)) {
    foreach ($envGateways as $gateway => $envName) {
        if ($gateway == $currentGateway) {
            var_export(putenv("APP_ENV=$envName"), true);
            break;
        }
    }
} else if (!empty($argv) && php_sapi_name() == "cli") {
    //For terminal
    foreach ($argv as $arg) {
        if (strpos($arg, '--env') !== false) {
            $env = explode("=", $arg);
            var_export(putenv("APP_ENV=" . $env[1]), true);
        }
    }
} else {
    var_export(putenv("APP_ENV=local"), true);
}

