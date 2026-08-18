<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Actions\Locations\GeocodeLocationAction;
use Modules\CMS\Filament\Resources\Locations\Schemas\LocationForm;
use Modules\CMS\Models\Location;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('flattens a geocoded location into the form geographic fields', function (): void {
    $location = new Location()->fill([
        'address' => 'Via del Corso 1',
        'city' => 'Rome',
        'province' => 'Lazio',
        'country' => 'Italy',
        'postcode' => '00100',
        'zone' => 'Centro',
    ]);
    $location->geolocation = new Point(41.9028, 12.4964);

    expect(LocationForm::geocodedFormState($location))->toBe([
        'address' => 'Via del Corso 1',
        'city' => 'Rome',
        'province' => 'Lazio',
        'country' => 'Italy',
        'postcode' => '00100',
        'zone' => 'Centro',
        'geolocation' => '41.9028, 12.4964',
    ]);
});

it('omits empty fields and geolocation when absent', function (): void {
    $location = new Location()->fill([
        'city' => 'Rome',
        'country' => 'Italy',
    ]);

    expect(LocationForm::geocodedFormState($location))->toBe([
        'city' => 'Rome',
        'country' => 'Italy',
    ]);
});

it('resolves a location from the geocode action and maps it to form state', function (): void {
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

    $result = app(GeocodeLocationAction::class)('Rome', null, null, null);
    $location = is_array($result) ? ($result[0] ?? null) : $result;

    expect($location)->toBeInstanceOf(Location::class);

    $state = LocationForm::geocodedFormState($location);

    expect($state)->toHaveKeys(['city', 'country'])
        ->and($state['city'])->toBe('Rome')
        ->and($state['country'])->toBe('Italy');
});
