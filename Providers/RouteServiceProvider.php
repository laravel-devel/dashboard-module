<?php

namespace Modules\DevelDashboard\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\DevelDashboard\Http\Controllers';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapDashboardRoutes();
        $this->mapPublicRoutes();
    }

    /**
     * Define the (admin) dashboard routes for the module.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapDashboardRoutes()
    {
        Route::middleware([
            'web',
            \Modules\DevelDashboard\Http\Middleware\DashboardAccess::class,
            \Devel\Http\Middleware\CheckRoutePermissions::class,
        ])
        ->prefix(config('develdashboard.dashboard_uri'))
        ->as('dashboard.')
        ->namespace($this->moduleNamespace)
        ->group(module_path('DevelDashboard', '/Routes/dashboard.php'));
    }

    /**
     * Define the public routes for the module.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapPublicRoutes()
    {
        $namespace = config('develdashboard.auth_controllers_namespace');

        Route::middleware([
            'web',
            \Devel\Http\Middleware\CheckRoutePermissions::class,
        ])
        ->prefix(config('develdashboard.dashboard_uri'))
        ->namespace($namespace ?: $this->moduleNamespace)
        ->as('dashboard.auth.')
        ->group(module_path('DevelDashboard', '/Routes/public.php'));
    }
}
