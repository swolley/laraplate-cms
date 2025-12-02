<?php

declare(strict_types=1);

namespace Modules\Cms\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Preset;
use Override;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    #[Override]
    public function boot(): void
    {
        Event::listen([
            'eloquent.saved: ' . Entity::class,
            'eloquent.deleted: ' . Entity::class,
        ], function (): void {
            $this->clearEntityCache();
        });

        Event::listen([
            'eloquent.saved: ' . Preset::class,
            'eloquent.deleted: ' . Preset::class,
            'eloquent.forceDeleted: ' . Preset::class,
        ], function (): void {
            $this->clearPresetCache();
        });
    }

    private function clearEntityCache(): void
    {
        Cache::forget(new Entity()->getCacheKey());
        $this->clearPresetCache();
    }

    private function clearPresetCache(): void
    {
        Cache::forget(new Preset()->getCacheKey());
    }
}
