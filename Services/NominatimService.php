<?php

declare(strict_types=1);

namespace Modules\Cms\Services;

use Override;
use Exception;
use Modules\Cms\Models\Location;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Cms\Services\Contracts\GeocodingServiceInterface;

final class NominatimService implements GeocodingServiceInterface
{
    private const string BASE_URL = 'https://nominatim.openstreetmap.org';

    /**
     * Singleton instance of the service.
     */
    private static ?self $instance = null;

    /**
     * Protected constructor to enforce singleton pattern.
     */
    private function __construct()
    {
        // Inizializzazione del servizio
    }

    /**
     * Get service instance (singleton pattern).
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    #[Override]
    public function search(
        string $query,
        ?string $city = null,
        ?string $province = null,
        ?string $country = null,
        int $limit = 1,
    ): array|Location|null {
        return RateLimiter::attempt(
            'nominatim',
            1,
            function () use ($query, $city, $province, $country, $limit) {
                try {
                    // Genera una chiave cache più robusta
                    $cache_key = $this->generateCacheKey($query, $city, $province, $country, $limit);

                    if (Cache::has($cache_key)) {
                        return Cache::get($cache_key);
                    }

                    $result = $this->performSearch($query, $city, $province, $country, $limit);

                    // Prova prima con i tag
                    if (Cache::supportsTags()) {
                        Cache::tags('geocoding')->put($cache_key, $result, config('cache.duration.long'));
                    } else {
                        Cache::put($cache_key, $result, config('cache.duration.long'));
                    }

                    return $result;
                } catch (Exception $e) {
                    Log::error('Nominatim geocoding cache error: ' . $e->getMessage());

                    // Se la cache fallisce, esegui comunque la ricerca
                    return $result ?? null;
                }
            },
            1,
        );
    }

    private function generateCacheKey(
        string $query,
        ?string $city,
        ?string $province,
        ?string $country,
        int $limit,
    ): string {
        $params = ['query' => $query, 'city' => $city, 'province' => $province, 'country' => $country, 'limit' => $limit];

        return md5(serialize(array_filter($params)));
    }

    private function performSearch(
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

        if ($city) {
            $params['city'] = $city;
        }

        if ($province) {
            $params['province'] = $province;
        }

        if ($country) {
            $params['country'] = $country;
        }

        $response = Http::withHeaders([
            'User-Agent' => config('app.name') . ' Application',
        ])->get(self::BASE_URL . '/search', $params);

        if (! $response->successful() || empty($response->json())) {
            return $limit > 1 ? [] : null;
        }

        $result = $response->json();

        if ($limit > 1) {
            return array_map(fn (array $result) => $this->getAddressDetails($result), $result);
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
    private function getAddressDetails(array $result): Location
    {
        $address = $result['address'];

        return (new Location())->fill([
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
}
