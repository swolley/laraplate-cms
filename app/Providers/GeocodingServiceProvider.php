<?php

declare(strict_types=1);

namespace Modules\Cms\Providers;

use Override;
use Illuminate\Support\ServiceProvider;
use Modules\Cms\Services\NominatimService;
use Modules\Cms\Services\GoogleMapsService;
use Modules\Cms\Services\Contracts\GeocodingServiceInterface;

final class GeocodingServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(GeocodingServiceInterface::class, fn ($app) => match (config('services.geocoding.provider', 'nominatim')) {
            'google' => GoogleMapsService::getInstance(),
            default => NominatimService::getInstance(),
        });
    }
}
