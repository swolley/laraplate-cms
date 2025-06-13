<?php

declare(strict_types=1);

namespace Modules\Cms\Providers;

use Exception;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Modules\Core\Overrides\ServiceProvider;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Traits\PathNamespace;
use Override;

/**
 * @property \Illuminate\Foundation\Application $app
 */
final class CmsServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Cms';

    protected string $nameLower = 'cms';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     * @throws Exception
     */
    #[Override]
    public function register(): void
    {
        if (! Module::find('Core')) {
            throw new Exception('Core is required and must be enabled');
        }

        $this->registerConfig();

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(GeocodingServiceProvider::class);

        // $this->initializeEntities();
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    #[Override]
    public function provides(): array
    {
        return [];
    }

    /**
     * Register commands in the format of Command::class.
     */
    private function registerCommands(): void
    {
        $module_commands_subpath = config('modules.paths.generator.command.path');
        $commands = $this->inspectFolderCommands($module_commands_subpath);

        $this->commands($commands);
    }

    /**
     * Register command Schedules.
     */
    private function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    private function inspectFolderCommands(string $commandsSubpath): array
    {
        $modules_namespace = config('modules.namespace');
        $files = glob(module_path($this->name, $commandsSubpath . DIRECTORY_SEPARATOR . '*.php'));

        return array_map(
            fn ($file): string => sprintf('%s\\%s\\%s\\%s', $modules_namespace, $this->name, Str::replace(['app/', '/'], ['', '\\'], $commandsSubpath), basename($file, '.php')),
            $files,
        );
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->nameLower)) {
                $paths[] = $path . '/modules/' . $this->nameLower;
            }
        }

        return $paths;
    }

    // protected function initializeEntities(): void
    // {

    //     try {
    //         if (! Schema::hasTable('entities')) {
    //             return;
    //         }
    //         $entity_cache_key = 'cms.entities.cache';

    //         $entities = Cache::get($entity_cache_key, collect());

    //         if ($entities->isEmpty()) {
    //             $entities = Entity::query()->withoutGlobalScopes()->get();
    //             Cache::forever($entity_cache_key, $entities);
    //             Content::resolveChildTypes($entities);
    //         }
    //     } catch (\Exception $e) {
    //         report($e);
    //     }
    // }
}
