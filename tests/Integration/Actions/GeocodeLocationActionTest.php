<?php

declare(strict_types=1);

use Modules\CMS\Actions\Locations\GeocodeLocationAction;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Contracts\Geocoding\GeocodingResult;
use Modules\Core\Contracts\Geocoding\IGeocodingService;

uses(TestCase::class);

afterEach(function (): void {
    Mockery::close();
});

it('invokes geocoding service', function (): void {
    $service = Mockery::mock(IGeocodingService::class);
    $service->shouldReceive('search')
        ->once()
        ->with('query', 'city', 'province', 'country')
        ->andReturn(null);

    $action = new GeocodeLocationAction($service);

    expect($action('query', 'city', 'province', 'country'))->toBeNull();
});

it('maps multiple geocoding results to locations', function (): void {
    $service = Mockery::mock(IGeocodingService::class);
    $service->shouldReceive('search')
        ->once()
        ->with('', null, null, 'Italy')
        ->andReturn([
            new GeocodingResult(name: 'Rome', country: 'Italy', latitude: 41.9028, longitude: 12.4964),
            new GeocodingResult(name: 'Milan', country: 'Italy', latitude: 45.4642, longitude: 9.19),
        ]);

    $action = new GeocodeLocationAction($service);

    $locations = $action(null, null, null, 'Italy');

    expect($locations)->toHaveCount(2)
        ->and($locations[0]->name)->toBe('Rome')
        ->and($locations[0]->geolocation)->not->toBeNull()
        ->and($locations[1]->name)->toBe('Milan');
});

it('maps a single geocoding result to a location', function (): void {
    $service = Mockery::mock(IGeocodingService::class);
    $service->shouldReceive('search')
        ->once()
        ->with('Rome', null, null, null)
        ->andReturn(new GeocodingResult(name: 'Rome', latitude: 41.9028, longitude: 12.4964));

    $action = new GeocodeLocationAction($service);

    $location = $action('Rome', null, null, null);

    expect($location)->toBeInstanceOf(Modules\CMS\Models\Location::class)
        ->and($location->name)->toBe('Rome')
        ->and($location->geolocation)->not->toBeNull();
});
