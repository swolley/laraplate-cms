<?php

declare(strict_types=1);

namespace Modules\Cms\Services\Contracts;

use Modules\Cms\Models\Location;

interface GeocodingServiceInterface
{
    /**
     * Search for locations using address components.
     *
     * @param  string  $query  Main search query (address/place)
     * @param  null|string  $city  City name
     * @param  null|string  $province  Province/state name
     * @param  null|string  $country  Country name
     * @param  int  $limit  Maximum number of results
     * @return null|array<int,Location>|Location
     */
    public function search(
        string $query,
        ?string $city = null,
        ?string $province = null,
        ?string $country = null,
        int $limit = 1,
    ): array|Location|null;
}
