<?php

/**
 * @author Ali Abbas
 * @description
 *   For General parameters
 */
return array(
    'show_errors_on_app' => true,
    'base_app_version' => 4,
    "ipstack" => "http://api.ipstack.com/",
    "shared_post_deeplink_staging" => "fayvoapp://fayvo-post.us-west-2.elasticbeanstalk.com/redirect/store",
    "shared_post_deeplink_production" => "fayvoapp://fayvo-post-production.us-west-2.elasticbeanstalk.com/redirect/store",
    "shared_post_deeplink_local" => "fayvoapp://fayvo-post.us-west-2.elasticbeanstalk.com/redirect/store",
    "shared_post_deeplink_browser" => "http://fayvo.com/",
    "itune_store" => "https://itunes.apple.com/us/app/fayvo/id1141717555?mt=8",
    "google_store" => "https://play.google.com/store/apps/details?id=com.fayvo&hl=en",
    "default_bg_color" => "#FFFFFF",
    "api_key" => env("IP_STACK_KEY"),
    'interaction_trigger_interval'=>3600,
    'activities_actions' => array(
        'like' => 1,
        'follow' => 2,
        'addPost' => 3,
        'profileVisit' => 4,
        'postVisit' => 5,
        'comment' => 6,
        'tagUser' => 7,
        'commentMentionUser' => 8,
        'accept_friend_request' => 9,
        'refayved' => 10,
    ),
    'mem_cache_keys' => array(
        'you-activities' => 'you-activities-',
        'following-activities' => 'following-activities-',
        'profile' => 'profile-',
        'login' => 'login-',
        'offset' => 'offset-',
        'block-user-list' => 'block-user-list-',
        'recent-you' => 'recent-you-',
        'trending-views' => 'trending4-views-',
        'trending-location' => 'trending-location-',
        'latest-trendings' => 'latest4-trending-',
        'latest-trendings-count' => 'latest4-trending-count-',
        'location-trendings' => 'location-trending-',
        'admin-trending-cache' => 'admin-trending-cache',
        'admin-trending-likes' => 'admin-trending-likes-',
        'people-cache' => 'people-cache-',
        'post-cache' => 'post-cache-',
        'people-viewed-cache' => 'people-viewed-cache-',
        'people-viewed-es-cache' => 'people-viewed-es-cache-',
        'place-cache' => 'place-cache',
        'place-location-cache' => 'place-location-cache',
        'home-cache' => 'home-cache-',
        'fs-cache' => 'fs-cache-',
        'box-cache' => 'box-cache-',
        'post-media-cache' => 'post-media-cache-',
        'uploaded-post-cache' => 'uploaded-post-cache-',
        'edit-post-cache' => 'edit-post-cache-',
        'user-chat-converstion' => 'user-conversation-',
        'post-ids-cache' => 'post-ids-cache',
        'post-like-cache' => 'post-like-cache-',
        'post-comment-cache' => 'post-comment-cache-',
        'user-following-list-cache' => 'user-following-list-',
        'user-requests-sent-list-cache' => 'user-request-sent-list-',
        'personal-profile-user' => 'personal-profile-user-',
        'ip-address-info' => 'ip-address-info-',
        'ip-lat-lon' => 'ip-latlon-',
    ),
    'home_limit' => 280,
    'box_posts_limit' => 200,
    'posts_limit' => 40,
    'place_posts_limit' => 280,
    'you_limit' => 500,
    'following_limit' => 800,
    'user_chat_limit' => 500,
    //trendings
    'trending_sub_nlc_query_limit' => 4000,
    'trending_sub_query_follow_limit' => 3000,
    'trending_n_l_s_limit' => 600,
    'trending_follow_likes_limit' => 450,
    'trending_admin_limit' => 150,
    'trending_limit' => 520,
    'people_limit' => 400,
    'score_actions' => array(
        'like' => 1,
        'comment' => 2,
        'view' => 3,
        'dislike' => -1,
        'delete_comment' => -2,
    ),
    'post_like_average_limit' => 220,
    'view_types' => array(
        'post' => 0,
        'profile' => 1,
    ),
    'view_types_from' => array(
        0 => 'post',
        1 => 'profile',
    ),
    'post_types_db' => array(
        1 => 'Picture',
        2 => 'Video',
        3 => 'Text',
        4 => 'Location',
        5 => 'Multimedia',
        6 => 'Web'
    ),
    'multi_media_types' => array(
        'picture' => 1,
        'video' => 2,
        'multi_media' => 5,
    ),
    'post_types' => array(
        1 => array(
            'id' => 1,
            'title' => 'Picture',
            'picture' => 'http:fayvo.com/assets/img/post-images/picture.jpg'
        ),
        2 => array(
            'id' => 2,
            'title' => 'Video',
            'picture' => 'http:fayvo.com/assets/img/post-images/video.jpg'
        ),
        3 => array(
            'id' => 3,
            'title' => 'Text',
            'picture' => 'http:fayvo.com/assets/img/post-images/text.jpg'
        ),
        4 => array(
            'id' => 4,
            'title' => 'Location',
            'picture' => 'http:fayvo.com/assets/img/post-images/web.jpg'
        ),
        5 => array(
            'id' => 5,
            'title' => 'Multimedia',
            'picture' => 'http:fayvo.com/assets/img/post-images/web.jpg'
        ),
        6 => array(
            'id' => 6,
            'title' => 'Web',
            'picture' => 'http:fayvo.com/assets/img/post-images/web.jpg'
        )
    ),
    'ml_url' => [
        "user_signup_interest_pair_url" => env("USER_SIGNUP_INTEREST_PAIR_URL"),
        "user_signup_interest_token" => env("USER_SIGNUP_INTEREST_TOKEN"),
    ],
    "box_permissions" => array(
        0 => ["A", "M", "F"], // me
        1 => ["A", "F"], // i am following
        2 => ["A"], // public
        3 => [] // no one
    ),
    'message_privacies' => array(
        0 => 'Only Friends',
        1 => 'Every One',
        2 => 'Private'
    ),
    'genders' => array(
        null => '',
        0 => '',
        1 => 'male',
        2 => 'female',
    ),
    'gender_keys' => array(
        '' => 0,
        'male' => 1,
        'female' => 2,
    ),
    'liked_types' => array(
        1 => 'post',
        2 => 'people'
    ),
    "saudi_location" => [
        "lat" => 23.88,
        "lon" => 45.07,
    ],
    "allowd_hosts_access" => [
        "192.168.1.140", "192.168.1.192", "fayvo-admin.test"
    ],
    'google_geocode_key' => env("GOOGLE_GEOCODE_API_KEY"),
    'upload_post_micro_service' => env('UPLOAD_POST_MICRO_SERVICE'),
    'post_micro_service' => env('POST_MICRO_SERVICE'),
    'user_micro_service' => env('USER_MICRO_SERVICE'),
    'user_micro_service_dev' => env('USER_MICRO_SERVICE_DEV'),
    'user_profile' => env('USER_PROFILER'),
    'user_profiler_cache' => env('USER_PROFILER_CACHE'),
    'node_micro_service' => env('NODE_MICRO_SERVICE', 'http://localhost:3000/'),
    'node_mvc_micro_service' => env('NODE_MVC_MICRO_SERVICE', 'http://localhost:3000/'),
    'profiler_micro_service' => env('PROFILER_MICRO_SERVICE', 'http://localhost:8002/'),
    "default_location" => [74.3587, 31.5204],
    "auth_key" => env('QR_RAPID_API_AUTH_KEY'),
    'post_type_index' => array(
        'picture' => 1,
        'video' => 2,
        'text' => 3,
        'location' => 4,
        'multimedia' => 5,
        'web' => 6,
        'search' => 7,
        'api' => 7,
        'product' => 8,
    ),
    "allowed_post_types" => [
        "" => ["media"],
        "4-1" => ["media", "search"],
        "4-2" => ["media", "search"]
    ],
    "media_type_posts" => [1, 2, 5],
    /**
     * Following integer does not exist in box and post table, will be considered as archived
     */
    "no_content_int" => 4,
    "referral_campaign" => [
        "status" => env('REFERRAL_CAMPAIGN_STATUS'),
        "line_1" => env('REFERRAL_LINE_1'),
        "line_2" => env('REFERRAL_LINE_2'),
    ],
    "white_list_ips" => ["103.8.112.6", "127.0.0.1", "::1", getenv('IP_ALLOWED')],
    "internal_services" => ['microservice_user', 'microservice_qucik_create', 'microservice_post', 'microservice_archive', 'microservice_upload_post', 'microservice_chat'],
    "internal_service_id" => "microservice_post",
    "production_test_users" => [76473, 60371, 37387, 57708, 37264, 48211, 48681, 79442, 18368, 57870, 59367, 38058, 87, 48834, 35514, 35964, 35892, 32701, 32318, 37212, 31132, 35798],
    'search_posts' => [7],
    'post_source_types' => [
        "youtube" => "youtube",
        "ibook" => "ibook",
        "imdb" => "imdb",
        "google" => "google",
        "itunes" => "itunes",
        "web" => "web"
    ],
    'item_type_icons' => array(
        'book' => 'http://d1lkqsdr30qepu.cloudfront.net/production/item-type/book.png',
        'drama' => 'http://d1lkqsdr30qepu.cloudfront.net/production/item-type/drama.png',
        'food' => 'http://d1lkqsdr30qepu.cloudfront.net/production/item-type/food.png',
        'place' => 'http://d1lkqsdr30qepu.cloudfront.net/production/item-type/place.png',
        'movie' => 'http://d1lkqsdr30qepu.cloudfront.net/production/item-type/movie.png',
        'music' => 'http://d1lkqsdr30qepu.cloudfront.net/production/item-type/music.png',
        'television' => 'http://d1lkqsdr30qepu.cloudfront.net/production/item-type/television.png',
        'url' => 'http://d1lkqsdr30qepu.cloudfront.net/production/item-type/url.png',
        'video' => 'http://d1lkqsdr30qepu.cloudfront.net/production/item-type/video.png',
        'other' => 'http://d1lkqsdr30qepu.cloudfront.net/production/item-type/other.png',
        'game' => 'http://d1lkqsdr30qepu.cloudfront.net/production/item-type/game_new.png',
        'game_old' => 'http://d1lkqsdr30qepu.cloudfront.net/production/item-type/game.png',
    ),
    'redis_keys' => [
        'home_top_trending' => 'home-top-trending',
        'user_home_top_trending' => 'home-user-top-trending-',
        'user_top_interests' => 'user-top-interests-',
        'last_location_redis_expire_min' => 10800,
        'redis_expire_min' => 10800,
        'client_ip' => 'client-ip-',
        'user_last_location' => 'user-last-location-',
        'device_last_location' => 'device-last-location-',
        'user_home_session' => 'user-home-session-',
        'shared_api_count' => 'fayvo-shared-api-count-',
        'shared_api_related_content' => 'fayvo-shared-api-related-content-',
        //super keys
        'followers' => 'followers',
        'followings' => 'followings',
        'users' => 'users',
        'boxes' => 'boxes',
        'locations' => 'locations',
        'api' => 'api-attributes',
        'post_tags' => 'post_tag_users',
        'top_coments' => 'top_coments',
        'comment_count' => 'comment_count',
        'reactions' => 'reactions',
        'is_liked' => 'is_liked_',
        'like_count' => 'like_count',
        'bookmarks' => 'bookmarks_',
        'is_fayved' => 'is_fayved_',
        'fayvs' => 'fayvs',
    ],
    "related_imdb_types" => array(
        "Television" => "tv",
        "Movie" => "movie",
        "Video" => "video",
        "Music" => "music",
        "Book" => "book",
        "Url" => "url",
        "Food" => "food",
        "Place" => "place",
        "Tv" => "tv",
    ),
    'object_prefix' => array(
        'user' => 'U',
        'device' => 'D',
    ),
    'aws_sqs' => array(
        'ml_interest_history_queue' => env("SQS_PREFIX") . env("ML_INTEREST_HISTORY_SQS_NAME"),
    ),
    "related_source_types" => array(
        "google" => "places",
        "youtube" => "videos",
        "place" => "places",
        "web" => "web",
        "imdb" => "cinema",
        "itunes" => "music",
        "ibook" => "books",
    ),
    "analytics_server" => env("ANALYTICS_SERVER", ""),
    'post_item_types' => array(
        'other' => 0,
        'music' => 1,
        'video' => 2,
        'place' => 3,
        'location' => 3,
        'food' => 4,
        'url' => 5,
        'movie' => 6,
        'drama' => 7,
        'television' => 8,
        'book' => 9,
        'game' => 10
    ),
    "item_type_by_number" => array(
        0 => 'other',
        1 => 'music',
        2 => 'video',
        3 => 'place',
        4 => 'food',
        5 => 'url',
        6 => 'movie',
        7 => 'drama',
        8 => 'television',
        9 => 'book',
        10 => 'game'
    ),
    'ml_security_key' => env('ML_REQUEST_AUTH_KEY'),
    'x_api_key' => env("X_API_KEY"),
);

