<?php

namespace App\Providers;

use App\Http\Middleware\AllowAnyOrigin;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapWellKnownRoutes();

        $this->mapOAuthRoutes();

        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "/.well-known" routes for the application.
     *
     * These routes are public and stateless, and readable from any origin. They
     * load before the web routes, whose catch-all would otherwise match them.
     *
     * @return void
     */
    protected function mapWellKnownRoutes()
    {
        Route::middleware(AllowAnyOrigin::class)->group(base_path('routes/well-known.php'));
    }

    /**
     * Define Handover's own "/oauth" routes, such as userinfo.
     *
     * These routes are stateless and authenticate with a bearer token.
     *
     * @return void
     */
    protected function mapOAuthRoutes()
    {
        Route::prefix('oauth')
             ->middleware('api')
             ->group(base_path('routes/oauth.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }
}
