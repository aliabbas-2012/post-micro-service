<?php

$image_file = getcwd() . "/config/" . getenv("APP_ENV") . "/image.php";

if ($image_file) {
    require_once $image_file;
} else {
    require_once getcwd() . "/config/production/image.php";
}

$image_config["default_pictures"] = ["male_default.jpg", "female_default.jpg"];
$image_config["default_pictures_by_gender"] = [
    "other" => "male_default.jpg",
    "unknown" => "male_default.jpg",
    "male" => "male_default.jpg",
    "female" => "female_default.jpg"
];

$image_config["video_base_url"] = $image_config["fayvo-sample-cdn-url"] . $image_config["ENV_FOLDER"] . $image_config["POST_FOLDER"] . $image_config["post_videos_source"];
$image_config["user_thumb_base_url"] = $image_config["fayvo-sample-cdn-url"] . $image_config["ENV_FOLDER"] . $image_config["USER_FOLDER"] . $image_config["profile_thumb"];

return $image_config;

//return array(
//    //site url
//    'site_url' => URL::to('/') . "/",
//    'user_post_increment' => 5,
//    'post_like_increment' => 1,
//    'post_comment_increment' => 1,
//    'post_decrement_date' => '2017-06-08',
////    's3_url' => 'http://d24ooz60y4g1w7.cloudfront.net/',
//    's3_url' => 'http://d2flqmogg10inr.cloudfront.net/',
//    's3_fayvo_live' => 'http://d2flqmogg10inr.cloudfront.net/',
//    's3_fayvo-sample' => 'http://d1lkqsdr30qepu.cloudfront.net/',
//    's3_uploading_url' => 'https://s3-us-west-2.amazonaws.com/favyo-live/',
//    's3_fayvo_uploads_url' => 'http://d6e2u2lgcialg.cloudfront.net/',
//    #****************   Post Images    ********************
//    // posts image uploading url's
//    'post_original_image_url' => 'assets/post_images/original/',
//    'post_thumb1_url' => 'assets/post_images/thumbnail1/',
//    'post_thumb2_url' => 'assets/post_images/thumbnail2/',
//    'post_img_medium_url' => 'assets/post_images/mediumImages/',
//    // image thumbnail sizes
//    'post_thumb1_width' => 150,
//    'post_thumb1_height' => 200,
//    'post_thumb2_width' => 400,
//    'post_mini_thumb_width' => 180,
//    'post_mini_thumb_height' => 180,
//    # *************  Post Videos  *****************
//    //post video uploading url's
//    'post_video_url' => 'assets/videos/',
//    'post_video_original_url' => 'assets/videos/original/',
//    'post_video_thumbnail_url' => 'assets/videos/thumnails/',
//    'post_video_gif_thumbnail_url' => 'assets/videos/gif_thumnails/',
//    'post_video_original_thumbnail_url' => 'assets/videos/new_thumnails/',
//    'post_video_gif_original_thumbnail_url' => 'assets/videos/new_gif_thumnails/',
//    'post_video_mini_thumbnail_url' => 'assets/videos/mini_thumnails/',
//    'post_video_gif_frames' => 'assets/videos/frames',
//    //---multi post testing and understanding
//    'multi_post' => 'assets/multi_post_test/original/',
//    'multi_post_medium' => 'assets/multi_post_test/medium/',
//    'multi_post_mini' => 'assets/multi_post_test/mini/',
//    'multi_post_video_path' => 'assets/multi_post_test/video/',
//    'multi_post_video' => 'assets/multi_post_test/video/original/',
//    'multi_post_video_fmpeg_url' => 'assets/multi_post_test/video/fmpeg/',
//    'multi_post_video_thumbnail_url' => 'assets/multi_post_test/video/thumbnails/',
//    'multi_post_video_mini_thumbnail_url' => 'assets/multi_post_test/video/mini_thumbnails/',
//    'multi_post_video_gif_url' => 'assets/multi_post_test/video/gif/',
//    //post video thumbnail size 
//    'post_video_thumnail_width' => '1160x775',
//    //post video thumbnail crop time (in seconds) 
//    'post_video_thumnail_time' => 0,
//    # *************  Post Videos  *****************
//    'post_audio_url' => 'assets/audio/',
//    # *************  Box Images  *****************
//    'box_url' => 'assets/Box/',
//    'default_box_logo' => 'assets/category_images/croped/',
//    # *************  CAtegory Images  *****************
//    //category image url
//    'cat_img_url' => 'assets/category_images/',
//    'cat_img_cropped_url' => 'assets/category_images/croped/',
//    //Profile image url fayvo-live bucket
//    'profile_original_fayvo_live' => 'assets/profile_images/original/',
//    'profile_thumb_fayvo_live' => 'assets/profile_images/thumbnail1/',
//    'profile_medium_fayvo_live' => 'assets/profile_images/mediumImages/',
//    //Profile image url fayvo-sample bucket
//    'profile_original_fayvo-sample' => 'users/original/',
//    'profile_thumb_fayvo-sample' => 'users/thumnb/',
//    'profile_medium_fayvo-sample' => 'users/medium/',
//    //Box imae url
//    'box_img_url' => 'assets/box_images/',
//    'box_img_medium_url' => 'assets/box_images/croped_',
//    'empty_box_img_url' => 'assets/box_images/fayvo-logo-1.png',
//    // image thumbnail sizes
//    'profile_thumb1_width' => 150,
//    'profile_thumb2_width' => 400,
//    'post_images_original_fayvo-sample' => env('post_images_original', 'images/original/'),
//    'post_images_home_fayvo-sample' => env('post_images_original', 'images/home/'),
//    'post_images_home_fayvo-live' => env('post_images_original', 'images/home/'),
//    'post_images_original_fayvo-live' => env('post_images_original', 'images/original/'),
//    'post_images_original_fayvo_live' => 'assets/post_images/original/',
//    'post_images_medium_fayvo-sample' => env('post_images_medium', 'images/medium/'),
//    'post_images_medium_fayvo-live' => env('post_images_medium', 'images/medium/'),
//    'post_images_medium_fayvo_live' => 'assets/post_images/mediumImages/',
//    'post_images_thumb_fayvo-sample' => env('post_images_thumbs', 'images/thumb/'),
//    'post_images_thumb_fayvo-live' => env('post_images_thumbs', 'images/thumb/'),
//    'post_images_thumb_fayvo_live' => 'assets/post_images/thumbnail1/',
//    //cdn urls
//    'POST_CDN_URL' => env('POST_CDN_URL', 'http://d1lkqsdr30qepu.cloudfront.net/'),
//    'POST_CDN_FULL_URL' => env('POST_CDN_FULL_URL', 'http://d1lkqsdr30qepu.cloudfront.net/posts/'),
//    'TMP_DIR' => env('TMP_DIR', '/tmp'),
//    'POST_BUCKET' => env('POST_BUCKET', 'fayvo-sample'),
//    'post_images_path' => env('post_images_path', 'images'),
//    'post_images_original' => env('post_images_original', 'images/original/'),
//    'post_images_home' => env('post_images_medium', 'images/home/'),
//    'post_images_medium' => env('post_images_medium', 'images/medium/'),
//    'post_images_thumb' => env('post_images_thumbs', 'images/thumb/'),
//    'post_videos_source' => env('post_videos_source', 'videos/source/'),
//    'post_videos_original' => env('post_videos_original', 'videos/original/'),
//    'post_videos_home' => env('post_videos_medium', 'videos/home/'),
//    'post_videos_medium' => env('post_videos_medium', 'videos/medium/'),
//    'post_videos_thumb' => env('post_videos_thumb', 'videos/thumb/'),
//    'post_collage' => env('post_collage', 'collage/'),
//    'fayvo_live_default_box' => 'boxes/default.png',
//    //cdn urls
//    'POST_CDN_URL' => env('POST_CDN_URL', 'http://d1lkqsdr30qepu.cloudfront.net/'),
//    'POST_CDN_FULL_URL' => env('POST_CDN_FULL_URL', 'http://d1lkqsdr30qepu.cloudfront.net/posts/'),
//    'fayvo_live-cdn-url' => 'http://d2flqmogg10inr.cloudfront.net/assets/',
//    'fayvo-sample-staging-cdn-url' => env('fayvo-sample-staging-new-cdn-url', 'http://d1lkqsdr30qepu.cloudfront.net/staging/'),
//    'fayvo-sample-local-cdn-url' => env('fayvo-sample-staging-new-cdn-url', 'http://d1lkqsdr30qepu.cloudfront.net/staging/'),
//    'fayvo-sample-testing-cdn-url' => env('fayvo-sample-testing-cdn-url', 'http://d1lkqsdr30qepu.cloudfront.net/testing/'),
//    'fayvo-sample-production-cdn-url' => env('fayvo-sample-production-new-cdn-url', 'http://d1lkqsdr30qepu.cloudfront.net/production/'),
//    'TMP_DIR' => env('TMP_DIR', '/tmp'),
//    'USER_FOLDER' => env('USER_FOLDER', 'users/'),
//    'POST_FOLDER' => env('POST_FOLDER', 'posts/'),
//    'FAYVO_BUCKET' => env('FAYVO_BUCKET', 'fayvo-sample'),
//    'post_images_path' => env('post_images_path', 'images'),
//    //post images and videos
//    'fayvo-sample_post_images_original' => env('fayvo-sample_post_images_original', 'images/original/'),
//    'fayvo-sample_post_images_home' => env('fayvo-sample_post_images_home', 'images/home/'),
//    'fayvo-sample_post_images_medium' => env('fayvo-sample_post_images_medium', 'images/medium/'),
//    'fayvo-sample_post_images_thumb' => env('fayvo-sample_post_images_thumb', 'images/thumb/'),
//    'fayvo-sample_post_videos_source' => env('fayvo-sample_post_videos_source', 'videos/source/'),
//    'fayvo-live_post_videos_source' => env('fayvo-sample_post_videos_source', 'videos/source/'),
//    'fayvo-sample_post_videos_original' => env('fayvo-sample_post_videos_original', 'videos/original/'),
//    'fayvo-sample_post_videos_home' => env('fayvo-sample_post_videos_home', 'videos/home/'),
//    'fayvo-sample_post_videos_medium' => env('fayvo-sample_post_videos_medium', 'videos/medium/'),
//    'fayvo-sample_post_videos_thumb' => env('fayvo-sample_post_videos_thumb', 'videos/thumb/'),
//    'fayvo-live_post_videos_thumb' => env('fayvo-sample_post_videos_thumb', 'videos/thumb/'),
//    'fayvo-sample_post_videos_gif' => env('fayvo-sample_post_videos_gif', 'videos/gif/'),
//    'fayvo-sample_post_collage' => env('fayvo-sample_post_collage', 'collage/'),
//    'fayvo-sample_post_location' => env('fayvo-sample_post_location', 'location/'),
//    //fayvo live
//    'fayvo_live_post_images_original' => 'post_images/original/',
//    'fayvo_live_post_images_home' => 'post_images/original/',
//    'fayvo_live_post_images_medium' => 'post_images/mediumImages/',
//    'fayvo_live_post_images_thumb' => 'post_images/thumbnail1/',
//    'fayvo_live_post_videos_source' => 'videos/',
//    'fayvo_live_post_videos_original' => 'videos/thumnails/',
//    'fayvo_live_post_videos_home' => 'videos/thumnails/',
//    'fayvo_live_post_videos_medium' => 'videos/thumnails/',
//    'fayvo_live_post_videos_thumb' => 'videos/thumnails/',
//    'fayvo_live_post_location' => env('fayvo_live_post_location', 'location/'),
//    // users
//    'fayvo-sample_user_original' => env('fayvo-sample_user_original', 'original/'),
//    'fayvo-sample_user_medium' => env('fayvo-sample_user_medium', 'medium/'),
//    'fayvo-sample_user_thumb' => env('ayvo-sample_user_thumb', 'thumb/'),
//    // for fayvo_live
//    'fayvo_live_user_original' => 'original/',
//    'fayvo_live_user_thumb' => 'thumbnail1/',
//    'fayvo_live_user_medium' => 'mediumImages/',
//    'post_media_dir_live' => 'staging4/posts/',
//    'post_media_dir_production' => 'production/posts/',
//    'post_media_dir_staging' => 'staging/',
//);
