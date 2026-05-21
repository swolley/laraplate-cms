<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\CMS\Models\Location;
use Modules\CMS\Services\NominatimService;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    Http::fake();
    Cache::flush();
});

it('includes optional address params in nominatim request', function (): void {
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'address' => [
                    'road' => 'Via Roma',
                    'city' => 'Milan',
                    'state' => 'MI',
                    'country' => 'Italy',
                    'postcode' => '20100',
                ],
                'lat' => 45.46,
                'lon' => 9.19,
            ],
        ], 200),
    ]);

    $service = NominatimService::getInstance();
    $result = $service->search('q', 'Milan', 'MI', 'Italy');

    expect($result)->toBeInstanceOf(Location::class);

    Http::assertSent(function ($request): bool {
        $url = $request->url();

        return str_contains($url, 'nominatim.openstreetmap.org/search')
            && str_contains($url, 'city=Milan')
            && str_contains($url, 'province=MI')
            && str_contains($url, 'country=Italy');
    });
});

it('returns null when nominatim request fails', function (): void {
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response('', 500),
    ]);

    $service = NominatimService::getInstance();

    expect($service->search('error'))->toBeNull();
});

it('returns null when nominatim responds empty for single result', function (): void {
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([], 200),
    ]);

    $service = NominatimService::getInstance();

    expect($service->search('nothing'))->toBeNull();
});

it('returns empty array when nominatim responds empty for multiple results', function (): void {
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([], 200),
    ]);

    $service = NominatimService::getInstance();

    expect($service->search('nothing', limit: 5))->toBe([]);
});

it('maps multiple results when limit is greater than one', function (): void {
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'address' => ['city' => 'A', 'road' => null, 'town' => null, 'village' => null, 'state' => null, 'country' => null, 'postcode' => null, 'suburb' => null],
                'lat' => 1.0,
                'lon' => 2.0,
            ],
            [
                'address' => ['city' => 'B', 'road' => null, 'town' => null, 'village' => null, 'state' => null, 'country' => null, 'postcode' => null, 'suburb' => null],
                'lat' => 3.0,
                'lon' => 4.0,
            ],
        ], 200),
    ]);

    $service = NominatimService::getInstance();
    $results = $service->search('places', limit: 5);

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(2)
        ->and($results[0])->toBeInstanceOf(Location::class)
        ->and($results[1]->city)->toBe('B');
});

it('builds search url for location', function (): void {
    $service = NominatimService::getInstance();
    $location = new Location;
    $location->latitude = 45.0;
    $location->longitude = 9.0;

    expect($service->url($location))->toBe(NominatimService::BASE_URL . '/search?q=45,9');
});

it('composes search url from address fields when coordinates are absent', function (): void {
    $service = NominatimService::getInstance();
    $location = new Location;
    $location->address = 'Via Roma 1';
    $location->postcode = '20100';
    $location->city = 'Milan';
    $location->province = 'MI';
    $location->country = 'Italy';

    $url = $service->url($location);

    expect($url)->toStartWith(NominatimService::BASE_URL . '/search?q=')
        ->and(rawurldecode($url))->toContain('Via Roma')
        ->and(rawurldecode($url))->toContain('Milan');
});

// --- Cache layer tests (Requirements 7.1, 7.2, 7.4, 7.5) ---

/**
 * Property 8: Geocoding cache prevents redundant HTTP calls.
 *
 * Validates: Requirements 7.1, 7.2, 7.4
 */
it('returns cached result on second call without making an HTTP request', function (): void {
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'address' => ['city' => 'Rome', 'road' => 'Via Veneto', 'state' => 'Lazio', 'country' => 'Italy', 'postcode' => '00187'],
                'lat' => 41.9,
                'lon' => 12.5,
            ],
        ], 200),
    ]);

    $service = NominatimService::getInstance();

    // First call — hits the API
    $first = $service->search('Rome');
    expect($first)->toBeInstanceOf(Location::class);
    Http::assertSentCount(1);

    // Second call with identical params — must use cache, no new HTTP request
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([], 500),
    ]);

    $second = $service->search('Rome');
    expect($second)->toBeInstanceOf(Location::class);

    // The 500 fake was never reached because the cache was used
    Http::assertNothingSent();
});

/**
 * Property 9: Failed geocoding requests are not cached.
 *
 * Validates: Requirement 7.5
 */
it('does not cache a failed HTTP response and returns null', function (): void {
    // Sequence: first call returns 503, second call returns a valid result.
    // If the failure were cached, the second call would never reach the HTTP layer.
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::sequence()
            ->push('', 503)
            ->push([
                [
                    'address' => ['city' => 'Somewhere', 'road' => null, 'state' => null, 'country' => null, 'postcode' => null],
                    'lat' => 10.0,
                    'lon' => 20.0,
                ],
            ], 200),
    ]);

    $service = NominatimService::getInstance();

    // First call: HTTP failure — must return null
    $result = $service->search('uncacheable-failure-query');
    expect($result)->toBeNull();

    // Verify nothing was written to cache for this key
    $params = ['query' => 'uncacheable-failure-query', 'city' => null, 'province' => null, 'country' => null, 'limit' => 1];
    $cache_key = \Modules\Core\Cache\CacheManager::key('geocoding', md5(serialize($params)));
    expect(\Illuminate\Support\Facades\Cache::get($cache_key))->toBeNull();

    // Second call: API now succeeds — must hit the API again (not return cached null)
    $retry = $service->search('uncacheable-failure-query');
    expect($retry)->toBeInstanceOf(Location::class);

    // Both requests were sent to the HTTP layer
    Http::assertSentCount(2);
});
