<?php

$image_config = array(
    //site url
    'fv-cdn-url' => 'https://d1fb6lv7det55g.cloudfront.net/',
    'fayvo-sample-cdn-url' => 'http://d1lkqsdr30qepu.cloudfront.net/',
    'fayvo-live-cdn-url' => 'http://d24ooz60y4g1w7.cloudfront.net/',
    'fayvo_live-cdn-url' => 'http://d24ooz60y4g1w7.cloudfront.net/',
    'FAYVO_BUCKET' => env('FAYVO_BUCKET', 'fayvo-sample'),
    'USER_FOLDER' => env('USER_FOLDER', 'users/'),
    'POST_FOLDER' => env('POST_FOLDER', 'posts/'),
    'ENV_FOLDER' => env('ENV_FOLDER', 'staging4/'),
    'post_images_original' => env('post_images_original', 'images/original/'),
    'post_images_home' => env('post_images_home', 'images/home/'),
    'post_images_medium' => env('post_images_medium', 'images/medium/'),
    'post_images_thumb' => env('post_images_thumb', 'images/thumb/'),
    'post_images_notification' => env('post_images_notification', 'images/notification/'),
    'post_videos_source' => env('post_videos_source', 'videos/source/'),
    'post_videos_original' => env('post_videos_original', 'videos/original/'),
    'post_videos_notification' => env('post_videos_notification', 'videos/notification/'),
    'post_videos_home' => env('post_videos_home', 'videos/home/'),
    'post_videos_medium' => env('post_videos_medium', 'videos/medium/'),
    'post_videos_thumb' => env('post_videos_thumb', 'videos/thumb/'),
    'post_videos_gif' => env('post_videos_gif', 'videos/gif/'),
    'collage' => env('collage', 'collage/'),
    //
    'profile_original' => 'original/',
    'profile_thumb' => 'thumb/',
    'profile_medium' => 'medium/',
    'default_male' => 'profile/male_default.jpg',
    'TMP_DIR' => env('TMP_DIR', '/tmp'),
    'profile_thumb1_width' => 150,
    'profile_thumb2_width' => 400,
    //
    'empty_new_box_url' => 'production/boxes/default.png',
    'empty_new_light_box_url' => 'production/boxes/default_light.jpg',
    //alias
    'alias_picture' => "images",
    'alias_video' => "videos",
);
