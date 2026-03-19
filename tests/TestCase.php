<?php

declare(strict_types=1);

namespace Modules\Cms\Tests;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Cms\Http\Controllers\LocationsController;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * CMS test case. Boots a Laravel application with the Cms module registered.
 * Core is required at runtime (CmsServiceProvider extends Core's ModuleServiceProvider):
 * run tests from the laraplate application or add the Core module as a dev dependency.
 */
abstract class TestCase extends TestbenchTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        if (! Schema::hasTable('entities')) {
            Artisan::call('migrate', [
                '--database' => 'sqlite',
                '--path' => realpath(__DIR__ . '/../database/migrations'),
                '--realpath' => true,
                '--force' => true,
            ]);
        }

        if (! Route::has('cms.locations.geocode')) {
            Route::middleware(['web', 'auth'])
                ->get('/cms/locations/geocode', [LocationsController::class, 'geocode'])
                ->name('cms.locations.geocode');
        }
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Modules\Cms\Providers\CmsServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('app.cipher', 'AES-256-CBC');
    }
}
