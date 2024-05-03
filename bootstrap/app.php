<?php

require_once "check_env.php";

//die;

use Dusterio\LumenPassport\LumenPassport;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__ . '/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
  |--------------------------------------------------------------------------
  | Create The Application
  |--------------------------------------------------------------------------
  |
  | Here we will load the environment and create the application instance
  | that serves as the central piece of this framework. We'll use this
  | application as an "IoC" container and router for this framework.
  |
 */
$app = new Laravel\Lumen\Application(
        realpath(__DIR__ . '/../')
);

$app->withFacades();

$app->withEloquent();

/*
  |--------------------------------------------------------------------------
  | Register Container Bindings
  |--------------------------------------------------------------------------
  |
  | Now we will register a few bindings in the service container. We will
  | register the exception handler and the console kernel. You may add
  | your own bindings here if you like or you can make another file.
  |
 */

$app->singleton('filesystem', function ($app) {
    return $app->loadComponent(
            'filesystems', Illuminate\Filesystem\FilesystemServiceProvider::class, 'filesystem'
    );
});

$app->singleton(
        Illuminate\Contracts\Debug\ExceptionHandler::class, App\Exceptions\Handler::class
);

$app->singleton(
        Illuminate\Contracts\Console\Kernel::class, App\Console\Kernel::class
);

//for the time being of QA testing
if (!empty($_SERVER["HTTP_X_AMZN_APIGATEWAY_API_ID"]) && $_SERVER["HTTP_X_AMZN_APIGATEWAY_API_ID"] == "uapcu7gzpk") {
    foreach ($_ENV as $env_key => $env_value) {
        if (substr($env_key, 0, 5) == "TEST_") {
            $env_key = str_replace("TEST_", "", $env_key);
            putenv("{$env_key}=$env_value");
        }
    }
}

//$app->alias('PushNotification', 'Davibennun\LaravelPushNotification\Facades\PushNotification');
//
//$app->alias('Collage', 'Tzsk\Collage\Facade\Collage');
// load cors configurations
$app->configure('cors');

// load mail configurations
$app->configure('mail');

// load database configurations
$app->configure('database');

// load general configurations
$app->configure('general');
// load images paths
$app->configure('image');
// configure for S3
$app->configure('filesystems');
$app->configure('cognito');
$app->configure('elastic_search');
$app->configure('security');
$app->configure('push-notification');
$app->configure('queue');
// load map icons config
$app->configure('g_map');

/*
  |--------------------------------------------------------------------------
  | Register Middleware
  |--------------------------------------------------------------------------
  |
  | Next, we will register the middleware with the application. These can
  | be global middleware that run before and after each request into a
  | route or middleware that'll be assigned to some specific routes.
  |
 */

$app->middleware([
    \Barryvdh\Cors\HandleCors::class,
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'throttle' => App\Http\Middleware\ThrottleRequests::class,
    'scopes' => \Laravel\Passport\Http\Middleware\CheckScopes::class,
    'scope' => \Laravel\Passport\Http\Middleware\CheckForAnyScope::class,
    'device_security' => App\Http\Middleware\MultiLayeredSecuirty::class,
    'localization' => App\Http\Middleware\Localization::class,
]);

/*
  |--------------------------------------------------------------------------
  | Add Aliases Here
  |--------------------------------------------------------------------------
  |
  | Here we will add aliases for all facades
  | and other classes we want to use as
  | Use Input
  |
 */





/*
  |--------------------------------------------------------------------------
  | Register Service Providers
  |--------------------------------------------------------------------------
  |
  | Here we will register all of the application's service providers which
  | are used to bind services into the container. Service providers are
  | totally optional, so you are not required to uncomment this line.
  |
 */

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(App\Providers\ModelObserverProvider::class);
$app->register(App\Providers\RepositoriesServiceProvider::class);
//$app->register(App\Providers\PoliciesServiceProvider::class);
$app->register(Barryvdh\Cors\ServiceProvider::class);
$app->register(Laravel\Passport\PassportServiceProvider::class);
$app->register(Dusterio\LumenPassport\PassportServiceProvider::class);
// register request form validation
$app->register(Pearl\RequestValidate\RequestServiceProvider::class);

$app->register(Tzsk\Collage\Provider\CollageServiceProvider::class);

$app->register(\Illuminate\Redis\RedisServiceProvider::class);

// service procder for Additonal Lumen artisan commands
if (env('APP_ENV') === 'local') {
    $app->bind(Illuminate\Database\ConnectionResolverInterface::class, Illuminate\Database\ConnectionResolver::class);
}
LumenPassport::routes($app);

require_once("change_db.php");
/*
  |--------------------------------------------------------------------------
  | Load The Application Routes
  |--------------------------------------------------------------------------
  |
  | Next we will include the routes file so that they can all be added to
  | the application. This will provide all of the URLs the application
  | can respond to, as well as the controllers that may handle them.
  |
 */

$app->router->group([
    'namespace' => 'App\Http\Controllers'
        ], function ($router) {
            require __DIR__ . '/../routes/web.php';
        });

return $app;
