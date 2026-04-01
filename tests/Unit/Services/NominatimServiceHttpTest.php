<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Modules\Cms\Models\Location;
use Modules\Cms\Services\NominatimService;
use Modules\Cms\Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    Http::fake();
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
