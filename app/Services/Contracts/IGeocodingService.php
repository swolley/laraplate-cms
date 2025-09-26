<?php

declare(strict_types=1);

namespace Modules\Cms\Services\Contracts;

use Modules\Cms\Models\Location;

interface IGeocodingService
{
    public static function getInstance(): self;

    /**
     * Search for locations using address components.
     *
     * @param  string  $query  Main search query (address/place)
     * @param  string|null  $city  City name
     * @param  string|null  $province  Province/state name
     * @param  string|null  $country  Country name
     * @param  int  $limit  Maximum number of results
     * @return array<int,Location>|Location|null
     */
    public function search(
        string $query,
        ?string $city = null,
        ?string $province = null,
        ?string $country = null,
        int $limit = 1,
    ): array|Location|null;

    public function url(Location $location): string;
}
