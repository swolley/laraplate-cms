<?php

namespace Modules\Cms\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Cms\Services\Contracts\GeocodingServiceInterface;
use Modules\Cms\Services\GoogleMapsService;
use Modules\Cms\Services\NominatimService;

class GeocodingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GeocodingServiceInterface::class, function ($app) {
            return match (config('services.geocoding.provider', 'nominatim')) {
                'google' => new GoogleMapsService(),
                default => new NominatimService(),
            };
        });
    }
}
