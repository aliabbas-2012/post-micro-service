<?php

$envVar = app()['env'];
$prefix = "";
if ($envVar == "testing") {
    $prefix = "TEST_";
}
$es = [
    'path' => env($prefix . 'ELASTICSEARCH_HOST'),
];

return $es;
