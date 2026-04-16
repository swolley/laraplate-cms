<?php

declare(strict_types=1);

namespace Modules\Cms\Tests;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('entities')) {
            $this->runOrderedSupportingMigrations();

            Artisan::call('migrate', [
                '--database' => 'sqlite',
                '--path' => realpath(__DIR__ . '/../database/migrations'),
                '--realpath' => true,
                '--force' => true,
            ]);
        }

        $this->ensurePresettablesVersioningColumns();

        if (! Route::has('cms.locations.geocode')) {
            Route::middleware(['web', 'auth'])
                ->get('/cms/locations/geocode', [LocationsController::class, 'geocode'])
                ->name('cms.locations.geocode');
        }
    }

    /**
     * Core entity/preset/field tables required by CMS models; avoid running the full Core migration set in Testbench (permission config, etc.).
     *
     * @return void
     */
    /**
     * Align the test schema with Core preset versioning (fields_snapshot, version) without
     * running trigger-heavy Core migrations.
     *
     * Keep the same sequence as Core migration `2026_02_27_000000_add_versioning_to_presettables_table`:
     * drop FK → replace uniques → add columns (nullable JSON, no server DEFAULT) → backfill →
     * NOT NULL, so MySQL, PostgreSQL, and SQLite stay consistent.
     */
    private function ensurePresettablesVersioningColumns(): void
    {
        if (! Schema::hasTable('presettables')) {
            return;
        }

        if (Schema::hasColumn('presettables', 'fields_snapshot')) {
            return;
        }

        Schema::table('presettables', function (Blueprint $table): void {
            $table->dropForeign('presettables_preset_FK');
            $table->dropUnique('presettables_preset_UN');
            $table->unsignedInteger('version')->default(1);
            $table->json('fields_snapshot')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['entity_id', 'preset_id', 'version'], 'presettables_version_UN');
            $table->foreign(['entity_id', 'preset_id'], 'presettables_preset_FK')
                ->references(['entity_id', 'id'])
                ->on('presets')
                ->cascadeOnDelete();
        });

        DB::table('presettables')->update(['fields_snapshot' => json_encode([])]);

        Schema::table('presettables', function (Blueprint $table): void {
            $table->json('fields_snapshot')->nullable(false)->change();
        });
    }

    private function runOrderedSupportingMigrations(): void
    {
        $paths = [
            realpath(__DIR__ . '/../../Core/database/migrations/2024_11_27_230300_create_entities_table.php'),
            realpath(__DIR__ . '/../database/migrations/2024_11_27_234217_create_templates_table.php'),
            realpath(__DIR__ . '/../../Core/database/migrations/2024_11_28_224314_create_presets_table.php'),
            realpath(__DIR__ . '/../../Core/database/migrations/2024_11_28_225525_create_fields_table.php'),
            realpath(__DIR__ . '/../../Core/database/migrations/2024_11_28_224400_create_entity_presets_relation_table.php'),
        ];

        foreach ($paths as $migration_path) {
            if ($migration_path === false) {
                continue;
            }

            Artisan::call('migrate', [
                '--database' => 'sqlite',
                '--path' => $migration_path,
                '--realpath' => true,
                '--force' => true,
            ]);
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
        $app->make(\Illuminate\Contracts\Config\Repository::class)->set('database.default', 'sqlite');
        $app->make(\Illuminate\Contracts\Config\Repository::class)->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app->make(\Illuminate\Contracts\Config\Repository::class)->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app->make(\Illuminate\Contracts\Config\Repository::class)->set('app.cipher', 'AES-256-CBC');
        $app->make(\Illuminate\Contracts\Config\Repository::class)->set('core.filament.tabs_counts_ttl_seconds', 60);
        $app->make(\Illuminate\Contracts\Config\Repository::class)->set(
            'auth.providers.users.model',
            \Modules\Cms\Tests\Support\User::class,
        );

        $config = $app->make(\Illuminate\Contracts\Config\Repository::class);
        $config->set(
            'media-library.image_optimizers',
            $config->get('media-library.image_optimizers', []),
        );
        if ($config->get('media-library.file_namer') === null) {
            $config->set(
                'media-library.file_namer',
                \Spatie\MediaLibrary\Support\FileNamer\DefaultFileNamer::class,
            );
        }
    }
}
