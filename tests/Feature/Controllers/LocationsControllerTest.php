<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Modules\Core\Models\User;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);
});

it('geocode returns location data for valid query', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Rome, Italy',
                'lat' => '41.9028',
                'lon' => '12.4964',
                'type' => 'city',
            ],
        ], 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'display_name',
                'lat',
                'lon',
                'type',
            ],
        ]);
});

it('geocode returns location data with city parameter', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Milan, Italy',
                'lat' => '45.4642',
                'lon' => '9.1900',
                'type' => 'city',
            ],
        ], 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Milan',
        'city' => 'Milan',
    ]));

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'display_name' => 'Milan, Italy',
                'lat' => '45.4642',
                'lon' => '9.1900',
                'type' => 'city',
            ],
        ]);
});

it('geocode returns location data with province parameter', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Rome, Lazio, Italy',
                'lat' => '41.9028',
                'lon' => '12.4964',
                'type' => 'city',
            ],
        ], 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
        'province' => 'Lazio',
    ]));

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'display_name' => 'Rome, Lazio, Italy',
                'lat' => '41.9028',
                'lon' => '12.4964',
                'type' => 'city',
            ],
        ]);
});

it('geocode returns location data with country parameter', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Rome, Italy',
                'lat' => '41.9028',
                'lon' => '12.4964',
                'type' => 'city',
            ],
        ], 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
        'country' => 'Italy',
    ]));

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'display_name' => 'Rome, Italy',
                'lat' => '41.9028',
                'lon' => '12.4964',
                'type' => 'city',
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
        ->assertJsonStructure([
            'error',
        ]);
});

it('geocode handles HTTP errors gracefully', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([], 500),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'error',
        ]);
});

it('geocode validates required query parameter', function (): void {
    /** @var TestCase $this */
    $response = $this->getJson(route('cms.locations.geocode'));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['q']);
});

it('geocode validates query parameter is string', function (): void {
    /** @var TestCase $this */
    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 123,
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['q']);
});

it('geocode validates query parameter is not empty', function (): void {
    /** @var TestCase $this */
    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => '',
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['q']);
});

it('geocode accepts optional city parameter', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Rome, Italy',
                'lat' => '41.9028',
                'lon' => '12.4964',
                'type' => 'city',
            ],
        ], 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
        'city' => 'Rome',
    ]));

    $response->assertStatus(200);
});

it('geocode accepts optional province parameter', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Rome, Lazio, Italy',
                'lat' => '41.9028',
                'lon' => '12.4964',
                'type' => 'city',
            ],
        ], 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
        'province' => 'Lazio',
    ]));

    $response->assertStatus(200);
});

it('geocode accepts optional country parameter', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Rome, Italy',
                'lat' => '41.9028',
                'lon' => '12.4964',
                'type' => 'city',
            ],
        ], 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
        'country' => 'Italy',
    ]));

    $response->assertStatus(200);
});

it('geocode returns correct response structure', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Rome, Italy',
                'lat' => '41.9028',
                'lon' => '12.4964',
                'type' => 'city',
            ],
        ], 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'display_name',
                'lat',
                'lon',
                'type',
            ],
        ]);
});

it('geocode handles network timeouts', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([], 408),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'error',
        ]);
});

it('geocode handles malformed responses', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response('invalid json', 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'error',
        ]);
});

it('geocode works with different query types', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Via del Corso, Rome, Italy',
                'lat' => '41.9028',
                'lon' => '12.4964',
                'type' => 'street',
            ],
        ], 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Via del Corso, Rome',
    ]));

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'display_name' => 'Via del Corso, Rome, Italy',
                'lat' => '41.9028',
                'lon' => '12.4964',
                'type' => 'street',
            ],
        ]);
});

it('geocode handles multiple results', function (): void {
    /** @var TestCase $this */
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Rome, Italy',
                'lat' => '41.9028',
                'lon' => '12.4964',
                'type' => 'city',
            ],
            [
                'display_name' => 'Rome, Georgia, USA',
                'lat' => '34.2570',
                'lon' => '-85.1647',
                'type' => 'city',
            ],
        ], 200),
    ]);

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'display_name',
                'lat',
                'lon',
                'type',
            ],
        ]);
});

it('geocode requires authentication', function (): void {
    /** @var TestCase $this */
    Auth::logout();

    $response = $this->getJson(route('cms.locations.geocode', [
        'q' => 'Rome',
    ]));

    $response->assertStatus(401);
});
