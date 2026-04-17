<?php

declare(strict_types=1);

use Modules\Cms\Actions\Locations\GeocodeLocationAction;
use Modules\Core\Contracts\Geocoding\IGeocodingService;
use Modules\Cms\Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    Mockery::close();
});

it('invokes geocoding service', function (): void {
    $service = Mockery::mock(IGeocodingService::class);
    $service->shouldReceive('search')
        ->once()
        ->with('query', 'city', 'province', 'country')
        ->andReturn(['result']);

    $action = new GeocodeLocationAction($service);

    expect($action('query', 'city', 'province', 'country'))->toBe(['result']);
});
