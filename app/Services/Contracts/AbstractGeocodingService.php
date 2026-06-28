<?php

declare(strict_types=1);

namespace Modules\CMS\Services\Contracts;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Models\Location;
use Throwable;

/**
 * Base implementation with URL building, rate-limited search, and cache helpers for geocoding providers.
 */
abstract class AbstractGeocodingService
{
    public function url(Location $location): string
    {
        return $this->getSearchUrl($this->buildSearchStringFromLocation($location));
    }

    /**
     * @return array<int, Location>|Location|null
     */
    public function search(
        string $query,
        ?string $city = null,
        ?string $province = null,
        ?string $country = null,
        int $limit = 1,
    ): array|Location|null {
        if (app()->environment('testing')) {
            return $this->performSearch($query, $city, $province, $country, $limit);
        }

        $rate_limit_key = 'nominatim:' . sha1(implode('|', [$query, $city ?? '', $province ?? '', $country ?? '', (string) $limit]));

        $result = RateLimiter::attempt(
            $rate_limit_key,
            60,
            fn (): array|Location|null => $this->performSearch($query, $city, $province, $country, $limit),
            1,
        );

        if ($result === false) {
            return null;
        }

        if ($result === true) {
            return null;
        }

        if (is_array($result)) {
            $locations = [];

            foreach ($result as $item) {
                if ($item instanceof Location) {
                    $locations[] = $item;
                }
            }

            return $locations;
        }

        if ($result instanceof Location) {
            return $result;
        }

        return null;
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $resolver
     * @return T
     */
    protected function rememberGeocodingThroughCache(string $key, int $ttl, Closure $resolver): mixed
    {
        try {
            return Cache::remember($key, $ttl, $resolver);
        } catch (Throwable) {
            return $resolver();
        }
    }

    /**
     * @return array<int, Location>|Location|null
     */
    abstract protected function performSearch(string $query, ?string $city, ?string $province, ?string $country, int $limit): array|Location|null;

    /**
     * @param  array<string, mixed>  $result
     */
    abstract protected function getAddressDetails(array $result): Location;

    abstract protected function getSearchUrl(string $search_string): string;

    private function buildSearchStringFromLocation(Location $location): string
    {
        $geolocation = $location->geolocation;

        if ($geolocation instanceof Point) {
            return $geolocation->latitude . ',' . $geolocation->longitude;
        }

        $address = (string) ($location->address ?? '');
        $postcode = (string) ($location->postcode ?? '');
        $city_name = (string) ($location->city ?? '');
        $province = (string) ($location->province ?? '');
        $country = (string) ($location->country ?? '');

        return $address . ' ' . $postcode . $city_name . ', ' . $province . ', ' . $country;
    }
}
