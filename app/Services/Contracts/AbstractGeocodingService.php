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
    public const string BASE_URL = '';

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
        $search = function () use ($query, $city, $province, $country, $limit): array|Location|null {
            try {
                $cache_key = $this->generateCacheKey($query, $city, $province, $country, $limit);

                return Cache::remember($cache_key, config('cache.duration.long'), function () use ($query, $city, $province, $country, $limit, $cache_key): Location|array|null {
                    $geocoded = $this->performSearch($query, $city, $province, $country, $limit);

                    if (method_exists(Cache::getStore(), 'tags')) {
                        Cache::tags(['geocoding'])->put($cache_key, $geocoded, config('cache.duration.long'));
                    }

                    return $geocoded;
                });
            } catch (Exception $exception) {
                Log::error('Nominatim geocoding cache error: ' . $exception->getMessage());

                return $this->performSearch($query, $city, $province, $country, $limit);
            }
        };

        if (app()->environment('testing')) {
            return $search();
        }

        $result = RateLimiter::attempt(
            'nominatim:' . md5($query . '|' . $city . '|' . $province . '|' . $country . '|' . $limit),
            60,
            $search,
            1,
        );

        if ($result === true || $result === false) {
            return null;
        }

        return $result;
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
