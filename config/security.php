<?php

return [
    "ipstack" => "http://api.ipstack.com/",
//    "api_key" => env("IP_STACK_KEY"),
    'current_ios_build_number' => '1.0',
    'current_android_build_number' => 33,
    'app_update_view_url' => "https://itunes.apple.com/us/app/fayvo/id1141717555?mt=8",
    //Make ips dynamic from env
    'white_list_ips' => ["103.8.112.6", "127.0.0.1", "::1"],
    'internal_services' => ['microservice_user', 'microservice_qucik_create', 'microservice_post', 'microservice_archive', 'microservice_upload_post', 'microservice_chat'],
    'internal_service_id' => 'microservice_post',
    
];
