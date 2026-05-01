<?php

declare(strict_types=1);

namespace Modules\CMS\Services;

use Illuminate\Support\Facades\Http;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Models\Location;
use Modules\CMS\Services\Contracts\AbstractGeocodingService;
use Override;

final class NominatimService extends AbstractGeocodingService
{
    public const string BASE_URL = 'https://nominatim.openstreetmap.org';

    #[Override]
    protected function performSearch(string $query, ?string $city, ?string $province, ?string $country, int $limit): array|Location|null
    {
        $query_params = [
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => $limit,
            'q' => $query,
        ];

        if ($city !== null && $city !== '') {
            $query_params['city'] = $city;
        }

        if ($province !== null && $province !== '') {
            $query_params['province'] = $province;
        }

        if ($country !== null && $country !== '') {
            $query_params['country'] = $country;
        }

        $response = Http::withHeaders([
            'User-Agent' => (string) config('app.name', 'Laraplate') . ' CMS Geocoder',
        ])->acceptJson()->get(self::BASE_URL . '/search', $query_params);

        if (! $response->successful()) {
            return null;
        }

        /** @var mixed $decoded */
        $decoded = $response->json();

        if (! is_array($decoded)) {
            return null;
        }

        if ($decoded === []) {
            return $limit === 1 ? null : [];
        }

        if ($limit === 1) {
            return $this->getAddressDetails($decoded[0]);
        }

        $slice = array_slice($decoded, 0, $limit);

        return array_values(array_map(fn (array $row): Location => $this->getAddressDetails($row), $slice));
    }

    #[Override]
    protected function getAddressDetails(array $result): Location
    {
        /** @var array<string, mixed> $address */
        $address = is_array($result['address'] ?? null) ? $result['address'] : [];

        $road = (string) ($address['road'] ?? '');
        $house_number = (string) ($address['house_number'] ?? '');
        $line = trim($road . ' ' . $house_number);

        $city = (string) ($address['city'] ?? $address['town'] ?? $address['village'] ?? '');
        $province = (string) ($address['state'] ?? $address['county'] ?? '');
        $country = (string) ($address['country'] ?? '');
        $postcode = (string) ($address['postcode'] ?? '');
        $zone = (string) ($address['suburb'] ?? '');

        $latitude = (float) ($result['lat'] ?? 0);
        $longitude = (float) ($result['lon'] ?? 0);

        return new Location()->fill([
            'address' => $line,
            'city' => $city,
            'province' => $province,
            'country' => $country,
            'postcode' => $postcode,
            'zone' => $zone,
            'geolocation' => new Point($latitude, $longitude),
        ]);
    }

    #[Override]
    protected function getSearchUrl(string $search_string): string
    {
        $query = preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $search_string) === 1
            ? $search_string
            : rawurlencode($search_string);

        return self::BASE_URL . '/search?q=' . $query;
    }
}
