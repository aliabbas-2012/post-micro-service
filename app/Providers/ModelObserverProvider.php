<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;
use \App\Models\User;
use \App\Observers\UserObserver;

class ModelObserverProvider extends ServiceProvider {

    public function boot() {

        parent::boot();

        User::observe(new UserObserver);
    }

}
