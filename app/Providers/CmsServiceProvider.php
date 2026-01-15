<?php

declare(strict_types=1);

namespace Modules\Cms\Providers;

use Exception;
use Modules\Cms\Services\DynamicContentsService;
use Modules\Core\Overrides\ModuleServiceProvider;
use Override;

/**
 * @property \Illuminate\Foundation\Application $app
 */
final class CmsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Cms';

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

        $this->app->register(GeocodingServiceProvider::class);

        $this->app->singleton(DynamicContentsService::class, DynamicContentsService::getInstance(...));
    }
}
