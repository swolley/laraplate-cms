<?php

declare(strict_types=1);

namespace Modules\CMS\Services\Contracts;

use Modules\CMS\Models\Location;

/**
 * Contract for CMS geocoding integrations (Nominatim-backed services, etc.).
 */
interface IGeocodingService
{
    public static function getInstance(): static;

    /**
     * @return array<int, Location>|Location|null
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
