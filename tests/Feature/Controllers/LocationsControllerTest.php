<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Modules\Cms\Models\Location;
use Modules\Cms\Services\Contracts\IGeocodingService;
use Modules\Cms\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    /** @var TestCase $this */
    $user = user_class()::factory()->create();
    $this->actingAs($user);
});

it('geocode returns location data for valid query', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'address' => [
                    'road' => 'Via del Corso',
                    'house_number' => '1',
                    'city' => 'Rome',
                    'state' => 'Lazio',
                    'country' => 'Italy',
                    'postcode' => '00100',
                    'suburb' => 'Centro',
                ],
                'lat' => 41.9028,
                'lon' => 12.4964,
            ],
        ], 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'address',
                'city',
                'province',
                'country',
                'postcode',
                'zone',
            ],
        ]);
});

it('geocode returns null when no results found', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([], 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'NonExistentPlace',
    ]));

    $response->assertStatus(200)
        ->assertJson([
            'data' => null,
        ]);
});

it('geocode handles API errors gracefully', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            'error' => 'Invalid request',
        ], 400),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
    ]));

    $response->assertStatus(200)
        ->assertJson([
            'data' => null,
        ]);
});

it('geocode validates required query parameter', function (): void {
    /** @var TestCase $this */
    $response = $this->getJson(route('cms.locations.geocode'));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['q']);
});

it('geocode validates minimum query length', function (): void {
    /** @var TestCase $this */
    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'ab',
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['q']);
});

it('geocode requires authentication', function (): void {
    /** @var TestCase $this */
    Auth::logout();

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
    ]));

    $response->assertStatus(401);
});

it('geocode maps geocoding exceptions to response error payload', function (): void {
    /** @var TestCase $this */
    $failing_service = new class implements IGeocodingService
    {
        public static function getInstance(): IGeocodingService
        {
            return new self;
        }

        public function search(
            string $query,
            ?string $city = null,
            ?string $province = null,
            ?string $country = null,
            int $limit = 1,
        ): array|Location|null {
            throw new RuntimeException('upstream geocoder unavailable');
        }

        public function url(Location $location): string
        {
            return '';
        }
    };

    $this->instance(IGeocodingService::class, $failing_service);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
    ]));

    $response->assertStatus(200)
        ->assertJsonPath('error', 'upstream geocoder unavailable');
});
