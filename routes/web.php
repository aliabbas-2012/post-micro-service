<?php

use Laravel\Lumen\Routing\Router;

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It is a breeze. Simply tell Lumen the URIs it should respond to
  | and give it the Closure to call when that URI is requested.
  |
 */
/** @var \Laravel\Lumen\Routing\Router $router */
$router->get('/', function () {
    return "Access denied";
});

// Generate random string
$router->get('appKey', function () {
    return str_random('32');
});

$router->group(['prefix' => 'api', 'middleware' => ['device_security', 'localization']], function () use ($router) {
    $router->post('postComment', ['uses' => 'CommentController@postComment']);
    $router->post('archiveComment', ['uses' => 'CommentController@archiveComment']);
    $router->get('postDetail', ['uses' => 'PostController@postDetail']);
    $router->get('internal/edit/postDetail', ['uses' => 'PostController@getInternalEditPostDetail']);

    $router->get('loadComments', ['uses' => 'CommentController@loadPostComments']);
    $router->get('home', ['uses' => 'PostController@home']);
    $router->get('v4/home', ['uses' => 'PostController@v4Home']);
    $router->post('v4/home', ['uses' => 'PostController@v4Home']);
    $router->get('liked-posts', ['uses' => 'LikeController@index']);

    $router->get('userPosts', ['uses' => 'BoxController@getUserPosts']);
    // End-Point depreciated and shifted to Profiler
    $router->get('boxPosts', ['uses' => 'BoxController@getBoxPosts']);

    $router->get('load-boxes', ['uses' => 'BoxController@getBoxesLoadMore']);
    $router->get('postCount', ['uses' => 'PostController@getPostCount']);
    $router->get('boxCount', ['uses' => 'BoxController@getBoxCount']);
    $router->get('tag-users', ['uses' => 'PostController@getPostTagUsers']);
    $router->get('comment-people', ['uses' => 'CommentController@commentPeopleList']);
    $router->get('post-permission-count', ['uses' => 'BoxController@getPostPermissionCount']);
    $router->get('search/post/detail', ['uses' => 'PostController@getSearchPostDetail']);
    $router->get('top/search/list', ['uses' => 'PostController@getTopSearchList']);
    $router->get('top/urlPost/list', ['uses' => 'PostController@getTopUrlPostList']);
    // New design home
    $router->post('user/friends/posts', ['uses' => 'FriendController@getFriendsPosts']);
    // route for shared post deep link Dec 26, 2018 ( Rizwan Saleem )
    $router->get('loadLikesComments', ['uses' => 'PostController@getPostLikesAndComments']);
    // Load new post detail + API detail
    $router->post('/post/api/detail', ['uses' => 'PostController@loadPostApiDetail']);
});

$router->group(['prefix' => 'dev-test'], function () use ($router) {
    $router->get('comment', ['uses' => 'TestController@getComment']);
    $router->get('post', ['uses' => 'TestController@getPost']);
});
/**
 * Internally call between micro-services
 */
$router->group(['prefix' => 'internal'], function () use ($router) {
    $router->get('boxGroupPosts', ['uses' => 'BoxController@getBoxes']);
    // This end-point internally call for fetching user bookmarked posts
    $router->post('user/bookmarked/posts', ['uses' => 'FriendController@getUserBookmarkedPosts']);
});

$router->get('post/share', ['uses' => 'SharePostController@sharedPost']);
$router->get('redirect/store', ['uses' => 'SharePostController@redirectToStore']);
