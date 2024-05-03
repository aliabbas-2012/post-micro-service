<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {

    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\ForgotPassword' => [
            'App\Listeners\EmailListener',
        ],
        'App\Events\ActivityEvent' => [
            'App\Listeners\ActivityListener'
        ],
        'App\Events\EngagementEvent' => [
            'App\Listeners\EngagementListener'
        ]
    ];

}
