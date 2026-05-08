<?php

declare(strict_types=1);

namespace Modules\CMS\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Models\Location;
use Modules\CMS\Services\NominatimService;
use Throwable;

/**
 * Geocodes a Location model asynchronously using the Nominatim API.
 *
 * Dispatched to the `geocoding` queue whenever a Location's address fields change.
 * Uses ThrottlesExceptions middleware to respect Nominatim's 1 req/s rate limit.
 */
final class GeocodeLocationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 120];

    public function __construct(private readonly Location $location)
    {
        $this->onQueue('geocoding');
    }

    /**
     * @return array<int, ThrottlesExceptions>
     */
    public function middleware(): array
    {
        // Nominatim enforces a 1 request/second rate limit.
        return [new ThrottlesExceptions(1, 1)];
    }

    /**
     * Geocode the Location and persist the resolved coordinates.
     */
    public function handle(NominatimService $service): void
    {
        $address = (string) ($this->location->address ?? '');
        $city = (string) ($this->location->city ?? '');
        $province = (string) ($this->location->province ?? '');
        $country = (string) ($this->location->country ?? '');

        $result = $service->search($address, $city, $province, $country, 1);

        if (! $result instanceof Location) {
            return;
        }

        $geolocation = $result->geolocation;

        if (! $geolocation instanceof Point) {
            return;
        }

        $this->location->geolocation = $geolocation;
        $this->location->save();
    }

    /**
     * Log the failure without modifying the existing coordinates.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('GeocodeLocationJob failed', [
            'location_id' => $this->location->getKey(),
            'error' => $exception->getMessage(),
        ]);
    }
}
