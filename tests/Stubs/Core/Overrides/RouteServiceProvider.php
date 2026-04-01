<?php

declare(strict_types=1);

namespace Modules\Core\Overrides;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as IlluminateRouteServiceProvider;

/**
 * Minimal stub for PHPStan when the Core module is not installed.
 */
abstract class RouteServiceProvider extends IlluminateRouteServiceProvider
{
    protected string $name;
}
