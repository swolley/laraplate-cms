<?php

declare(strict_types=1);

namespace Modules\Cms\Actions\Locations;

use Modules\Cms\Models\Location;
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
        return new Location()->fill($result->toArray());
    }
}
