<?php

declare(strict_types=1);

namespace Modules\Cms\Services\Contracts;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Cms\Models\Location;
use Override;

abstract class AbstractGeocodingService implements IGeocodingService
{
    public const BASE_URL = '';

    /**
     * Singleton instance of the service.
     */
    private static ?self $instance = null;

    abstract protected function performSearch(
        string $query,
        ?string $city,
        ?string $province,
        ?string $country,
        int $limit,
    ): array|Location|null;

    abstract protected function getAddressDetails(array $result): Location;

    abstract protected function getSearchUrl(string $search_string): string;

    /**
     * Get service instance (singleton pattern).
     */
    #[Override]
    public static function getInstance(): static
    {
        return self::$instance ??= new static();
    }

    public function url(Location $location): string
    {
        $search_string = $this->composeSearchString($location);

        return $this->getSearchUrl($search_string);
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

                    return Cache::remember($cache_key, config('cache.duration.long'), function () use ($query, $city, $province, $country, $limit, $cache_key): Location|array|null {
                        $result = $this->performSearch($query, $city, $province, $country, $limit);

                        // Store with tags if supported
                        if (Cache::supportsTags()) {
                            Cache::tags(Cache::getCacheTags('geocoding'))->put($cache_key, $result, config('cache.duration.long'));
                        }

                        return $result;
                    });
                } catch (Exception $exception) {
                    Log::error('Nominatim geocoding cache error: ' . $exception->getMessage());

                    // Se la cache fallisce, esegui comunque la ricerca
                    return $result ?? null;
                }
            },
            1,
        );
    }

    protected function composeSearchString(Location $location): string
    {
        if ($location->latitude && $location->longitude) {
            return $location->latitude . ',' . $location->longitude;
        }

        $search_string = '';

        if ($location->address) {
            $search_string .= $location->address;
        }

        if ($location->postcode) {
            $search_string .= ' ' . $location->postcode;
        }

        if ($location->city) {
            $search_string .= $location->city;
        }

        if ($location->province) {
            $search_string .= ', ' . $location->province;
        }

        if ($location->country) {
            $search_string .= ', ' . $location->country;
        }

        return $search_string;
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
}
