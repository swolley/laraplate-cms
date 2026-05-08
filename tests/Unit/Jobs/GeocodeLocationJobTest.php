<?php

declare(strict_types=1);

/**
 * GeocodeLocationJob tests.
 *
 * Do not assert ReflectionClass::isFinal(): tests/Pest.php enables DG\BypassFinals,
 * which reports final classes as non-final so Mockery can replace methods.
 */

use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Support\Facades\Log;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Jobs\GeocodeLocationJob;
use Modules\CMS\Models\Location;
use Modules\CMS\Services\NominatimService;

// ─────────────────────────────────────────────────────────────────────────────
// Structure & configuration (no DB required)
// ─────────────────────────────────────────────────────────────────────────────

it('implements ShouldQueue', function (): void {
    $reflection = new ReflectionClass(GeocodeLocationJob::class);

    expect($reflection->implementsInterface(Illuminate\Contracts\Queue\ShouldQueue::class))->toBeTrue();
});

it('uses the required queue traits', function (): void {
    $traits = array_keys((new ReflectionClass(GeocodeLocationJob::class))->getTraits());

    expect($traits)->toContain(Illuminate\Foundation\Bus\Dispatchable::class);
    expect($traits)->toContain(Illuminate\Queue\InteractsWithQueue::class);
    expect($traits)->toContain(Illuminate\Bus\Queueable::class);
    expect($traits)->toContain(Illuminate\Queue\SerializesModels::class);
});

it('has deleteWhenMissingModels property set to true', function (): void {
    $reflection = new ReflectionClass(GeocodeLocationJob::class);
    $property = $reflection->getProperty('deleteWhenMissingModels');

    expect($property->isPublic())->toBeTrue();

    // Verify the default value via the class source
    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('public bool $deleteWhenMissingModels = true');
});

it('has tries property set to 3', function (): void {
    $reflection = new ReflectionClass(GeocodeLocationJob::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('public int $tries = 3');
});

it('has backoff property set to [30, 60, 120]', function (): void {
    $reflection = new ReflectionClass(GeocodeLocationJob::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('[30, 60, 120]');
});

it('returns ThrottlesExceptions middleware', function (): void {
    $location = Mockery::mock(Location::class);
    $location->shouldReceive('onQueue')->andReturnSelf();

    $job = new GeocodeLocationJob($location);
    $middleware = $job->middleware();

    expect($middleware)->toHaveCount(1);
    expect($middleware[0])->toBeInstanceOf(ThrottlesExceptions::class);
});

it('has a handle method that accepts NominatimService', function (): void {
    $reflection = new ReflectionClass(GeocodeLocationJob::class);
    $method = $reflection->getMethod('handle');

    expect($method->isPublic())->toBeTrue();
    expect($method->getNumberOfParameters())->toBe(1);

    $param = $method->getParameters()[0];
    expect($param->getType()->getName())->toBe(NominatimService::class);
});

it('has a failed method that accepts Throwable', function (): void {
    $reflection = new ReflectionClass(GeocodeLocationJob::class);
    $method = $reflection->getMethod('failed');

    expect($method->isPublic())->toBeTrue();
    expect($method->getNumberOfParameters())->toBe(1);

    $param = $method->getParameters()[0];
    expect($param->getType()->getName())->toBe(Throwable::class);
});

it('sets the geocoding queue in the constructor', function (): void {
    $reflection = new ReflectionClass(GeocodeLocationJob::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain("onQueue('geocoding')");
});

// ─────────────────────────────────────────────────────────────────────────────
// Property 11: handle() updates Location coordinates on success
// Validates: Requirements 8.4
// ─────────────────────────────────────────────────────────────────────────────

it('updates location geolocation when service returns a valid result', function (): void {
    // Feature: performance-optimization, Property 11: Geocoding job updates Location coordinates on success
    $resolved_point = new Point(45.4642, 9.1900);

    // Mock the resolved Location returned by the geocoding service
    $resolved_location = Mockery::mock(Location::class);
    $resolved_location->shouldReceive('offsetExists')->with('geolocation')->andReturn(true);
    $resolved_location->shouldReceive('getAttribute')->with('geolocation')->andReturn($resolved_point);

    // Build the target location mock
    $location = Mockery::mock(Location::class);
    $location->shouldReceive('onQueue')->andReturnSelf();
    $location->shouldReceive('offsetExists')->andReturn(true);
    $location->shouldReceive('getAttribute')->with('address')->andReturn('Via Roma 1');
    $location->shouldReceive('getAttribute')->with('city')->andReturn('Milan');
    $location->shouldReceive('getAttribute')->with('province')->andReturn('MI');
    $location->shouldReceive('getAttribute')->with('country')->andReturn('Italy');
    $location->shouldReceive('setAttribute')->with('geolocation', $resolved_point)->once();
    $location->shouldReceive('save')->once();

    $service = Mockery::mock(NominatimService::class);
    $service->shouldReceive('search')
        ->once()
        ->with('Via Roma 1', 'Milan', 'MI', 'Italy', 1)
        ->andReturn($resolved_location);

    $job = new GeocodeLocationJob($location);
    $job->handle($service);
});

it('does not update location when service returns null', function (): void {
    $location = Mockery::mock(Location::class);
    $location->shouldReceive('onQueue')->andReturnSelf();
    $location->shouldReceive('offsetExists')->andReturn(true);
    $location->shouldReceive('getAttribute')->with('address')->andReturn('Via Roma 1');
    $location->shouldReceive('getAttribute')->with('city')->andReturn('Milan');
    $location->shouldReceive('getAttribute')->with('province')->andReturn('MI');
    $location->shouldReceive('getAttribute')->with('country')->andReturn('Italy');
    $location->shouldNotReceive('save');

    $service = Mockery::mock(NominatimService::class);
    $service->shouldReceive('search')->once()->andReturn(null);

    $job = new GeocodeLocationJob($location);
    $job->handle($service);
});

it('does not update location when service returns an array', function (): void {
    $location = Mockery::mock(Location::class);
    $location->shouldReceive('onQueue')->andReturnSelf();
    $location->shouldReceive('offsetExists')->andReturn(true);
    $location->shouldReceive('getAttribute')->with('address')->andReturn('Via Roma 1');
    $location->shouldReceive('getAttribute')->with('city')->andReturn('Milan');
    $location->shouldReceive('getAttribute')->with('province')->andReturn('MI');
    $location->shouldReceive('getAttribute')->with('country')->andReturn('Italy');
    $location->shouldNotReceive('save');

    $service = Mockery::mock(NominatimService::class);
    $service->shouldReceive('search')->once()->andReturn([]);

    $job = new GeocodeLocationJob($location);
    $job->handle($service);
});

it('does not update location when resolved location has no Point geolocation', function (): void {
    // Build a resolved Location mock with null geolocation
    $resolved_location = Mockery::mock(Location::class);
    $resolved_location->shouldReceive('offsetExists')->with('geolocation')->andReturn(false);
    $resolved_location->shouldReceive('getAttribute')->with('geolocation')->andReturn(null);

    $location = Mockery::mock(Location::class);
    $location->shouldReceive('onQueue')->andReturnSelf();
    $location->shouldReceive('offsetExists')->andReturn(true);
    $location->shouldReceive('getAttribute')->with('address')->andReturn('Via Roma 1');
    $location->shouldReceive('getAttribute')->with('city')->andReturn('Milan');
    $location->shouldReceive('getAttribute')->with('province')->andReturn('MI');
    $location->shouldReceive('getAttribute')->with('country')->andReturn('Italy');
    $location->shouldNotReceive('save');

    $service = Mockery::mock(NominatimService::class);
    $service->shouldReceive('search')->once()->andReturn($resolved_location);

    $job = new GeocodeLocationJob($location);
    $job->handle($service);
});

// ─────────────────────────────────────────────────────────────────────────────
// Property 12: failed() preserves coordinates on failure
// Validates: Requirements 8.5
// ─────────────────────────────────────────────────────────────────────────────

it('logs error on failure without calling save', function (): void {
    // Feature: performance-optimization, Property 12: Geocoding job preserves coordinates on failure
    $location = Mockery::mock(Location::class);
    $location->shouldReceive('onQueue')->andReturnSelf();
    $location->shouldReceive('getKey')->andReturn(42);
    $location->shouldNotReceive('save');
    $location->shouldNotReceive('setAttribute');

    $exception = new RuntimeException('Nominatim API unavailable');

    $job = new GeocodeLocationJob($location);

    // failed() must not throw and must not modify the model
    expect(fn () => $job->failed($exception))->not->toThrow(Throwable::class);
});

it('logs the location id and error message on failure', function (): void {
    Log::spy();

    $location = Mockery::mock(Location::class);
    $location->shouldReceive('onQueue')->andReturnSelf();
    $location->shouldReceive('getKey')->andReturn(99);

    $exception = new RuntimeException('timeout');

    $job = new GeocodeLocationJob($location);
    $job->failed($exception);

    Log::shouldHaveReceived('error')
        ->once()
        ->with('GeocodeLocationJob failed', Mockery::on(static function (array $context): bool {
            return isset($context['location_id'], $context['error'])
                && $context['location_id'] === 99
                && $context['error'] === 'timeout';
        }));
});
