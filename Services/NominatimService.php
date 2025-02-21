<?php

namespace Modules\Cms\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Cms\Models\Location;
use Modules\Cms\Services\Contracts\GeocodingServiceInterface;

class NominatimService implements GeocodingServiceInterface
{
    private const BASE_URL = 'https://nominatim.openstreetmap.org';
    private const CACHE_TTL = 86400; // 24 hours

    public function search(
        string $query,
        ?string $city = null,
        ?string $province = null,
        ?string $country = null,
        int $limit = 1
    ): array|Location|null {
        $cache_key = 'nominatim_' . md5($query . $city . $province . $country . $limit);

        return Cache::remember($cache_key, self::CACHE_TTL, function () use ($query, $city, $province, $country, $limit) {
            return RateLimiter::attempt(
                'nominatim',
                1,
                function () use ($query, $city, $province, $country, $limit) {
                    $params = [
                        'q' => $query,
                        'format' => 'json',
                        'addressdetails' => 1,
                        'limit' => $limit
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
                        'User-Agent' => config('app.name') . ' Application'
                    ])->get(self::BASE_URL . '/search', $params);

                    if (!$response->successful() || empty($response->json())) {
                        return $limit > 1 ? [] : null;
                    }

                    $result = $response->json();
                    if ($limit > 1) {
                        return array_map(fn(array $result) => $this->getAddressDetails($result), $result);
                    }

                    return $this->getAddressDetails($result[0]);
                },
                1
            );
        });
    }

    private function getAddressDetails(array $result): Location
    {
        $address = $result['address'];

        return Location::make([
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
