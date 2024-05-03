<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use App\Rules\CustomValidator;
// for morphic relation
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        // load custom helper classes
    }

    public function boot() {
        // customise model name for morphic relations
        Relation::morphMap([
            'media' => 'App\Models\MediaPost',
            'text' => 'App\Models\TextPost',
            'search' => 'App\Models\SearchPost',
            'url' => 'App\Models\UrlPost',
            'location' => 'App\Models\Location',
            'attributes' => 'App\Models\PostSearchAttribute',
        ]);
    }

}
