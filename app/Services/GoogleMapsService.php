<?php

declare(strict_types=1);

namespace Modules\Cms\Services;

use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\Http;
use Modules\Cms\Models\Location;
use Modules\Cms\Services\Contracts\AbstractGeocodingService;
use Override;

final class GoogleMapsService extends AbstractGeocodingService
{
    public const BASE_URL = 'https://maps.googleapis.com/maps/api/geocode';

    private readonly string $api_key;

    /**
     * Protected constructor to enforce singleton pattern.
     */
    private function __construct()
    {
        $this->api_key = (string) config('services.geocoding.api_key', '');
    }

    #[Override]
    protected function performSearch(
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
            return array_map($this->getAddressDetails(...), $results);
        }

        return $this->getAddressDetails($results[0]);
    }

    #[Override]
    protected function getSearchUrl(string $search_string): string
    {
        return self::BASE_URL . '/maps?q=' . $search_string . '&key=' . $this->api_key;
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
    #[Override]
    protected function getAddressDetails(array $result): Location
    {
        $components = [];

        foreach ($result['address_components'] as $component) {
            $type = $component['types'][0];
            $components[$type] = $component['long_name'];
        }

        return new Location()->fill([
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
