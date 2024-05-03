<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;
use App\Policies\UserPolicy;
use App\Policies\CommentPolicy;

class PoliciesServiceProvider extends ServiceProvider {

    protected $policies = [
        'App\Models' => 'App\Policies\ModelPolicy',
        PostComment::class => CommentPolicy::class
    ];

    public function boot(GateContract $gate) {

//        parent::boot();

        $this->registerPolicies($gate);

        //Register all policies here
//        Gate::policy(User::class, UserPolicy::class);

        Gate::policy(User::class, CommentPolicy::class);
        Gate::define('archive-comment', function ($user, $comment) {
            return $user->id == $comment->user_id;
        });
    }

}
