<?php

// copy this file to config.php
$envVar = app()['env'];
$prefix = "";
if ($envVar == "testing") {
    $prefix = "TEST_";
}
$aws = [
    'credentials' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],
    'region' => 'us-west-2',
    'version' => 'latest',
    'app_client_id' => env($prefix . 'COGNITO_APP_CLIENT_ID'),
    'app_client_secret' => env($prefix . 'COGNITO_APP_CLIENT_SECRET'),
    'app_admin_client_id' => env($prefix . 'COGNITO_APP_ADMIN_CLIENT_ID'),
    'app_admin_client_secret' => env($prefix . 'COGNITO_APP_ADMIN_CLIENT_SECRET', ''),
    'user_pool_id' => env($prefix . 'COGNITO_USER_POOL_ID'),
    "dynamo_db_archive_comment" => env("AWS_DYNAMO_COMMENT", "ProductionArchivedComments"),
    "activity_dynamodb_table" => env("ACTIVITY_DYNAMODB_TABLE", "ProductionActivityTrigger"),
    "dynamo_db_un_authorized_views" => env("DYNAMO_DB_UN_AUTHORIZED_VIEWS", "UnAuthorizedPostView"),
    "short_code_lambda_function" => env("SHORT_CODE_LAMBDA_FUNCTION", "serverlessrepo-fayvo-url-shortner-siteFunction-KSGTATX83QOF"),
    "short_code_dynamodb_table" => env("SHORT_CODE_DYNAMODB_TABLE", "serverlessrepo-fayvo-url-shortner-urlTable-OJGSUXAE77K2"),
];

return $aws;

