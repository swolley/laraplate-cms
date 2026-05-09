<?php

declare(strict_types=1);

namespace Modules\CMS\Providers;

use Exception;
use Modules\CMS\Observers\PlaceObserver;
use Modules\Core\Models\Place;
use Modules\Core\Overrides\ModuleServiceProvider;
use Override;

/**
 * @property \Illuminate\Foundation\Application $app
 */
final class CMSServiceProvider extends ModuleServiceProvider
{
    #[Override]
    protected string $name = 'CMS';

    #[Override]
    protected string $nameLower = 'cms';

    /**
     * Register the service provider.
     *
     * @throws Exception
     */
    #[Override]
    public function register(): void
    {
        parent::register();
    }

    /**
     * Boot the service provider.
     */
    #[Override]
    public function boot(): void
    {
        parent::boot();

        // Observe Place model to dispatch geocoding jobs when address fields change.
        // Address fields (address, city, province, country) are stored on Place via HasPlace,
        // so we must watch Place saves rather than Location saves.
        Place::observe(PlaceObserver::class);
    }
}
