<?php

declare(strict_types=1);

namespace Modules\Cms\Tests;

use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * CMS test case. Boots a Laravel application with the Cms module registered.
 * Core is required at runtime (CmsServiceProvider extends Core's ModuleServiceProvider):
 * run tests from the laraplate application or add the Core module as a dev dependency.
 */
abstract class TestCase extends TestbenchTestCase
{
    /**
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Modules\Cms\Providers\CmsServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
