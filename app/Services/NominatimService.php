<?php

declare(strict_types=1);

namespace Modules\CMS\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Models\Location;
use Modules\CMS\Services\Contracts\AbstractGeocodingService;
use Modules\Core\Cache\CacheManager;
use Override;

final class NominatimService extends AbstractGeocodingService
{
    public const string BASE_URL = 'https://nominatim.openstreetmap.org';

    #[Override]
    protected function performSearch(string $query, ?string $city, ?string $province, ?string $country, int $limit): array|Location|null
    {
        /** @var array{query: string, city: string|null, province: string|null, country: string|null, limit: int} $params */
        $params = [
            'query' => $query,
            'city' => $city !== '' ? $city : null,
            'province' => $province !== '' ? $province : null,
            'country' => $country !== '' ? $country : null,
            'limit' => $limit,
        ];

        $cache_key = CacheManager::key('geocoding', md5(serialize($params)));
        $ttl = (int) config('cms.geocoding.cache_ttl', 604800);

        /** @var array<int, Location>|Location|null $cached */
        $cached = Cache::get($cache_key);

        if ($cached !== null) {
            return $cached;
        }

        $result = $this->fetchFromApi($params);

        // Requirement 7.5: do NOT cache HTTP failures (null result).
        if ($result !== null) {
            Cache::put($cache_key, $result, $ttl);
        }

        return $result;
    }

    /**
     * Perform the actual HTTP request to the Nominatim API.
     *
     * Returns null on HTTP failure (result must NOT be cached).
     *
     * @param  array{query: string, city: string|null, province: string|null, country: string|null, limit: int}  $params
     * @return array<int, Location>|Location|null
     */
    private function fetchFromApi(array $params): array|Location|null
    {
        $query_params = [
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => $params['limit'],
            'q' => $params['query'],
        ];

        if ($params['city'] !== null) {
            $query_params['city'] = $params['city'];
        }

        if ($params['province'] !== null) {
            $query_params['province'] = $params['province'];
        }

        if ($params['country'] !== null) {
            $query_params['country'] = $params['country'];
        }

        $response = Http::withHeaders([
            'User-Agent' => config('app.name', 'Laraplate') . ' CMS Geocoder',
        ])->acceptJson()->get(self::BASE_URL . '/search', $query_params);

        if (! $response->successful()) {
            return null;
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            return null;
        }

        if ($decoded === []) {
            return $params['limit'] === 1 ? null : [];
        }

        if ($params['limit'] === 1) {
            return $this->getAddressDetails($decoded[0]);
        }

        $slice = array_slice($decoded, 0, $params['limit']);

        return array_values(array_map($this->getAddressDetails(...), $slice));
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
