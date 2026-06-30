<?php

declare(strict_types=1);

namespace Modules\CMS\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Models\Location;
use Modules\CMS\Services\Contracts\AbstractGeocodingService;
use Modules\CMS\Services\Contracts\IGeocodingService;
use Modules\Core\Cache\CacheManager;
use Override;

final class NominatimService extends AbstractGeocodingService implements IGeocodingService
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
        $ttl = $this->cacheTtlSeconds();

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

        $app_name = config('app.name');
        $user_agent = (is_string($app_name) ? $app_name : 'Laraplate') . ' CMS Geocoder';

        $response = Http::withHeaders([
            'User-Agent' => $user_agent,
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
            $normalized = $this->normalizeNominatimResult($decoded[0] ?? null);

            return $normalized !== null ? $this->getAddressDetails($normalized) : null;
        }

        $locations = [];

        foreach (array_slice($decoded, 0, $params['limit']) as $item) {
            $normalized = $this->normalizeNominatimResult($item);

            if ($normalized === null) {
                continue;
            }

            $locations[] = $this->getAddressDetails($normalized);
        }

        return $locations;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    #[Override]
    protected function getAddressDetails(array $result): Location
    {
        $address = is_array($result['address'] ?? null) ? $result['address'] : [];

        $road = $this->nominatimString($address['road'] ?? null);
        $house_number = $this->nominatimString($address['house_number'] ?? null);
        $line = trim($road . ' ' . $house_number);

        $city = $this->nominatimString($address['city'] ?? null);
        if ($city === '') {
            $city = $this->nominatimString($address['town'] ?? null);
        }
        if ($city === '') {
            $city = $this->nominatimString($address['village'] ?? null);
        }

        $province = $this->nominatimString($address['state'] ?? null);
        if ($province === '') {
            $province = $this->nominatimString($address['county'] ?? null);
        }

        $country = $this->nominatimString($address['country'] ?? null);
        $postcode = $this->nominatimString($address['postcode'] ?? null);
        $zone = $this->nominatimString($address['suburb'] ?? null);

        $latitude = is_numeric($result['lat'] ?? null) ? (float) $result['lat'] : 0.0;
        $longitude = is_numeric($result['lon'] ?? null) ? (float) $result['lon'] : 0.0;

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

    private function cacheTtlSeconds(): int
    {
        $ttl = config('cms.geocoding.cache_ttl', 604800);

        if (is_int($ttl)) {
            return $ttl;
        }

        if (is_numeric($ttl)) {
            return (int) $ttl;
        }

        return 604800;
    }

    private function nominatimString(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeNominatimResult(mixed $item): ?array
    {
        if (! is_array($item)) {
            return null;
        }

        $normalized = [];

        foreach ($item as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
