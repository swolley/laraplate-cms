<?php

namespace Modules\Cms\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Cms\Models\Location;
use Modules\Cms\Services\Contracts\GeocodingServiceInterface;

class GoogleMapsService implements GeocodingServiceInterface
{
    private const BASE_URL = 'https://maps.googleapis.com/maps/api/geocode';
    private const CACHE_TTL = 86400; // 24 hours

    public function __construct(
        private readonly string $api_key = ''
    ) {
        if (empty($this->api_key)) {
            $this->api_key = config('services.geocoding.api_key');
        }
    }

    public function search(
        string $query,
        ?string $city = null,
        ?string $province = null,
        ?string $country = null,
        int $limit = 1
    ): array|Location|null {
        $cache_key = 'gmaps_' . md5($query . $city . $province . $country . $limit);

        return Cache::remember($cache_key, self::CACHE_TTL, function () use ($query, $city, $province, $country, $limit) {
            return RateLimiter::attempt(
                'google_maps',
                1,
                function () use ($query, $city, $province, $country, $limit) {
                    // Costruiamo l'indirizzo completo
                    $address_components = array_filter([$query, $city, $province, $country]);
                    $full_address = implode(', ', $address_components);

                    $response = Http::get(self::BASE_URL . '/json', [
                        'address' => $full_address,
                        'key' => $this->api_key,
                        'limit' => $limit
                    ]);

                    if (!$response->successful() || $response->json()['status'] !== 'OK') {
                        return $limit > 1 ? [] : null;
                    }

                    $results = $response->json()['results'];

                    if ($limit > 1) {
                        return array_map(fn(array $result) => $this->getAddressDetails($result), $results);
                    }

                    return $this->getAddressDetails($results[0]);
                },
                1
            );
        });
    }

    private function getAddressDetails(array $result): Location
    {
        $components = [];
        foreach ($result['address_components'] as $component) {
            $type = $component['types'][0];
            $components[$type] = $component['long_name'];
        }

        return Location::make([
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
