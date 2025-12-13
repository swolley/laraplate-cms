<?php

declare(strict_types=1);

namespace Modules\Cms\Services;

use Illuminate\Support\Facades\Http;
use Modules\Cms\Models\Location;
use Modules\Cms\Services\Contracts\AbstractGeocodingService;
use Override;

final class NominatimService extends AbstractGeocodingService
{
    public const BASE_URL = 'https://nominatim.openstreetmap.org';

    #[Override]
    protected function performSearch(
        string $query,
        ?string $city,
        ?string $province,
        ?string $country,
        int $limit,
    ): array|Location|null {
        $params = [
            'q' => $query,
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => $limit,
        ];

        if (! in_array($city, [null, '', '0'], true)) {
            $params['city'] = $city;
        }

        if (! in_array($province, [null, '', '0'], true)) {
            $params['province'] = $province;
        }

        if (! in_array($country, [null, '', '0'], true)) {
            $params['country'] = $country;
        }

        $response = Http::withHeaders([
            'User-Agent' => config('app.name') . ' Application',
        ])->get(self::BASE_URL . '/search', $params);

        if (! $response->successful() || $response->json() === []) {
            return $limit > 1 ? [] : null;
        }

        $result = $response->json();

        if ($limit > 1) {
            return array_map($this->getAddressDetails(...), $result);
        }

        return $this->getAddressDetails($result[0]);
    }

    /**
     * @param array{
     *     address: array{
     *         road: string|null,
     *         house_number: string|null,
     *         city: string|null,
     *         town: string|null,
     *         village: string|null,
     *         state: string|null,
     *         country: string|null,
     *         postcode: string|null,
     *         suburb: string|null,
     *     },
     *     lat: float|null,
     *     lon: float|null,
     * } $result
     */
    #[Override]
    protected function getAddressDetails(array $result): Location
    {
        $address = $result['address'];

        return new Location()->fill([
            'address' => $address['road'] ? $address['road'] . ($address['house_number'] ? ' ' . $address['house_number'] : '') : null,
            'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
            'province' => $address['state'] ?? null,
            'country' => $address['country'] ?? null,
            'postcode' => $address['postcode'] ?? null,
            'latitude' => $result['lat'] ?? null,
            'longitude' => $result['lon'] ?? null,
            'zone' => $address['suburb'] ?? null,
        ]);
    }

    #[Override]
    protected function getSearchUrl(string $search_string): string
    {
        return self::BASE_URL . '/search?q=' . $search_string;
    }
}
