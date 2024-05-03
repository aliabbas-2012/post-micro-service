<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use \App\Models\BoxBookmark;
use \App\Models\PostComment;
use \App\Models\UserPost;

class RepositoriesServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {

        $this->app->bind(\App\Repositories\Contracts\PostCommentRepository::class, function () {
            return new \App\Repositories\EloquentPostComment(new PostComment());
        });

        $this->app->bind(\App\Repositories\Contracts\PostRepository::class, function () {
            return new \App\Repositories\EloquentPost(new UserPost());
        });

        $this->app->bind(\App\Repositories\Contracts\BoxBookmarkRepository::class, function () {
            return new \App\Repositories\EloquentBoxBookmark(new BoxBookmark());
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return [
            PostCommentRepository::class,
            PostRepository::class,
        ];
    }

}
