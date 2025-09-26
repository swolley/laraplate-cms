<?php

declare(strict_types=1);

namespace Modules\Cms\Services;

use Exception;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Cms\Models\Location;
use Modules\Cms\Services\Contracts\IGeocodingService;
use Override;

final class GoogleMapsService implements IGeocodingService
{
    private const string BASE_URL = 'https://maps.googleapis.com/maps/api/geocode';

    /**
     * Singleton instance of the service.
     */
    private static ?self $instance = null;

    private readonly string $api_key;

    /**
     * Protected constructor to enforce singleton pattern.
     */
    private function __construct()
    {
        $this->api_key = (string) config('services.geocoding.api_key', '');
    }

    /**
     * Get service instance (singleton pattern).
     */
    #[Override]
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
            'google_maps',
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
                    Log::error('Google Maps geocoding cache error: ' . $e->getMessage());

                    // Se la cache fallisce, esegui comunque la ricerca
                    return $result ?? null;
                }
            },
            1,
        );
    }

    #[Override]
    public function url(Location $location): string
    {
        if ($location->latitude && $location->longitude) {
            $search_string = $location->latitude . ',' . $location->longitude;
        } else {
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
        }

        return self::BASE_URL . '/maps?q=' . $search_string . '&key=' . $this->api_key;
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
        // Costruiamo l'indirizzo completo
        $address_components = array_filter([$query, $city, $province, $country]);
        $full_address = implode(', ', $address_components);

        $response = Http::get(self::BASE_URL . '/json', [
            'address' => $full_address,
            'key' => $this->api_key,
            'limit' => $limit,
        ]);

        if (! $response->successful() || $response->json()['status'] !== 'OK') {
            return $limit > 1 ? [] : null;
        }

        $results = $response->json()['results'];

        if ($limit > 1) {
            return array_map(fn (array $result) => $this->getAddressDetails($result), $results);
        }

        return $this->getAddressDetails($results[0]);
    }

    /**
     * Extracts address details from a Google Maps API result array and returns a Location model instance.
     *
     * @param  array  $result  The result array from Google Maps API. Expected structure:
     *                         [
     *                         'address_components' => array of arrays with keys 'types' (array) and 'long_name' (string),
     *                         'geometry' => [
     *                         'location' => [
     *                         'lat' => float,
     *                         'lng' => float
     *                         ]
     *                         ]
     *                         ]
     *
     * @throws MassAssignmentException
     */
    private function getAddressDetails(array $result): Location
    {
        $components = [];

        foreach ($result['address_components'] as $component) {
            $type = $component['types'][0];
            $components[$type] = $component['long_name'];
        }

        return (new Location())->fill([
            'address' => $components['route'] ? $components['route'] . ($components['street_number'] ? ' ' . $components['street_number'] : '') : null,
            'city' => $components['locality'] ?? $components['administrative_area_level_3'] ?? null,
            'province' => $components['administrative_area_level_1'] ?? null,
            'country' => $components['country'] ?? null,
            'postcode' => $components['postal_code'] ?? null,
            'latitude' => $result['geometry']['location']['lat'] ?? null,
            'longitude' => $result['geometry']['location']['lng'] ?? null,
            'zone' => $components['sublocality'] ?? null,
        ]);
    }
}
