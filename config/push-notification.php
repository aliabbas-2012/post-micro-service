<?php

/**
 * Send push notification configurations
 */
return array(
    'appNameIOS' => array(
        'environment' => 'development',
        'certificate_development' => base_path('public/pemfiles/fayvoDevVoip.pem'),
        //      'certificate_development' => base_path('public/pemfiles/apns-dev.pem'),
//        'certificate_production' => base_path('public/pemfiles/apns-prod.pem'),
        'certificate_production' => base_path('public/pemfiles/fayvoVoip.pem'),
        'passPhrase' => 'password',
        'service' => 'apns',
        'ios_push_notification_development' => 'ssl://gateway.sandbox.push.apple.com:2195',
        'ios_push_notification_production' => 'ssl://gateway.push.apple.com:2195',
    ),
    'appNameAndroid' => array(
        'environment' => 'production',
        'apiKey' => env('ANDROID_API_NOTI_KEY'),
        'service' => 'fcm',
    )
);
