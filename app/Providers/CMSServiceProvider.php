<?php

declare(strict_types=1);

namespace Modules\CMS\Providers;

use Exception;
use Modules\CMS\ApplicationContent\CmsApplicationContentRetrievalProvider;
use Modules\CMS\Graph\CmsGraphProvider;
use Modules\CMS\Observers\PlaceObserver;
use Modules\CMS\Services\CommentModerationAdapter;
use Modules\Core\ApplicationContent\Contracts\ApplicationContentRetrievalProviderRegistryInterface;
use Modules\Core\Graph\Contracts\GraphProviderRegistryInterface;
use Modules\Core\Models\Place;
use Modules\Core\Overrides\ModuleServiceProvider;
use Modules\Core\Services\ModerationAdapterRegistry;
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

        $this->app->register(ImportServiceProvider::class);
    }

    /**
     * Boot the service provider.
     */
    #[Override]
    public function boot(): void
    {
        parent::boot();

        $this->app
            ->make(GraphProviderRegistryInterface::class)
            ->register($this->app->make(CmsGraphProvider::class), 'cms');

        $this->app
            ->make(ApplicationContentRetrievalProviderRegistryInterface::class)
            ->register($this->app->make(CmsApplicationContentRetrievalProvider::class));

        // Observe Place model to dispatch geocoding jobs when address fields change.
        // Address fields (address, city, province, country) are stored on Place via HasPlace,
        // so we must watch Place saves rather than Location saves.
        Place::observe(PlaceObserver::class);

        $this->app->make(ModerationAdapterRegistry::class)
            ->register($this->app->make(CommentModerationAdapter::class));
    }
}
