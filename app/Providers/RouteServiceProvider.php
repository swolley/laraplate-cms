<?php

declare(strict_types=1);

namespace Modules\Cms\Providers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

final class RouteServiceProvider extends ServiceProvider
{
    private string $name = 'Cms';

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    private function getPrefix(): string
    {
        return Str::slug($this->name);
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    private function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->prefix('app')
            ->name($this->getPrefix() . '.')
            ->group([
                module_path($this->name, '/routes/web.php'),
            ]);
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    private function mapApiRoutes(): void
    {
        $name_prefix = $this->getPrefix();
        $route_prefix = 'api';
        Route::prefix("{$route_prefix}/v1")
            ->middleware($route_prefix)
            ->name("{$name_prefix}.{$route_prefix}.")
            ->group([
                module_path($this->name, '/routes/api.php'),
            ]);
    }
}
