<?php

namespace Modules\Cms\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Cms\Services\Contracts\GeocodingServiceInterface;
use Modules\Cms\Services\GoogleMapsService;
use Modules\Cms\Services\NominatimService;

class GeocodingServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(GeocodingServiceInterface::class, fn($app) => match (config('services.geocoding.provider', 'nominatim')) {
            'google' => GoogleMapsService::getInstance(),
            default => NominatimService::getInstance(),
        });
    }
}
