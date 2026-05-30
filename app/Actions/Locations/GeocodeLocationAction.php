<?php

declare(strict_types=1);

namespace Modules\CMS\Actions\Locations;

use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Models\Location;
use Modules\Core\Contracts\Geocoding\GeocodingResult;
use Modules\Core\Contracts\Geocoding\IGeocodingService;

final readonly class GeocodeLocationAction
{
    public function __construct(
        private IGeocodingService $geocodingService,
    ) {}

    public function __invoke(?string $query, ?string $city, ?string $province, ?string $country): array|Location|null
    {
        $result = $this->geocodingService->search($query ?? '', $city, $province, $country);

        if ($result === null) {
            return null;
        }

        if (is_array($result)) {
            return array_map($this->toLocation(...), $result);
        }

        return $this->toLocation($result);
    }

    private function toLocation(GeocodingResult $result): Location
    {
        $attributes = $result->toArray();
        $latitude = $attributes['latitude'] ?? null;
        $longitude = $attributes['longitude'] ?? null;
        unset($attributes['latitude'], $attributes['longitude']);

        $location = new Location()->fill($attributes);

        if ($latitude !== null && $longitude !== null) {
            $location->geolocation = new Point((float) $latitude, (float) $longitude);
        }

        return $location;
    }
}
