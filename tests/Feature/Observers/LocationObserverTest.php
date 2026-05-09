<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Jobs\GeocodeLocationJob;
use Modules\CMS\Models\Location;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Place;

uses(TestCase::class, RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Observer registration
// ─────────────────────────────────────────────────────────────────────────────

it('has LocationObserver registered via ObservedBy attribute', function (): void {
    $reflection = new ReflectionClass(Location::class);
    $attributes = $reflection->getAttributes(Illuminate\Database\Eloquent\Attributes\ObservedBy::class);

    expect($attributes)->not->toBeEmpty();

    $observer_classes = $attributes[0]->getArguments()[0];
    $observer_classes = is_array($observer_classes) ? $observer_classes : [$observer_classes];

    expect($observer_classes)->toContain(Modules\CMS\Observers\LocationObserver::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Property 10: Location creation dispatches GeocodeLocationJob
// Validates: Requirements 8.1
// ─────────────────────────────────────────────────────────────────────────────

it('dispatches GeocodeLocationJob when a new Location is created', function (): void {
    Queue::fake();

    Location::factory()->create([
        'address' => 'Via Roma 1',
        'city' => 'Milan',
        'province' => 'MI',
        'country' => 'Italy',
        'geolocation' => new Point(45.4642, 9.1900),
    ]);

    Queue::assertPushed(GeocodeLocationJob::class, 1);
});

it('dispatches GeocodeLocationJob to the geocoding queue on creation', function (): void {
    Queue::fake();

    Location::factory()->create([
        'geolocation' => new Point(45.4642, 9.1900),
    ]);

    Queue::assertPushedOn('geocoding', GeocodeLocationJob::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Address field updates via Place model (HasPlace bridge)
// Address fields are stored on the related Place model, not on Location directly.
// PlaceObserver handles dispatch when those fields change after creation.
// ─────────────────────────────────────────────────────────────────────────────

it('dispatches GeocodeLocationJob when address changes on the related Place', function (): void {
    Queue::fake();

    $location = Location::factory()->create([
        'address' => 'Via Roma 1',
        'city' => 'Milan',
        'province' => 'MI',
        'country' => 'Italy',
        'geolocation' => new Point(45.4642, 9.1900),
    ]);

    // Reset the queue after creation to test update scenario
    Queue::fake();

    // Update the Place directly (simulating what HasPlace does internally)
    $place = Place::query()->findOrFail($location->place_id);
    $place->update(['address' => 'Via Torino 5']);

    Queue::assertPushed(GeocodeLocationJob::class, 1);
});

it('dispatches GeocodeLocationJob when city changes on the related Place', function (): void {
    Queue::fake();

    $location = Location::factory()->create([
        'city' => 'Milan',
        'geolocation' => new Point(45.4642, 9.1900),
    ]);

    Queue::fake();

    $place = Place::query()->findOrFail($location->place_id);
    $place->update(['city' => 'Rome']);

    Queue::assertPushed(GeocodeLocationJob::class, 1);
});

it('dispatches GeocodeLocationJob when province changes on the related Place', function (): void {
    Queue::fake();

    $location = Location::factory()->create([
        'province' => 'MI',
        'geolocation' => new Point(45.4642, 9.1900),
    ]);

    Queue::fake();

    $place = Place::query()->findOrFail($location->place_id);
    $place->update(['province' => 'RM']);

    Queue::assertPushed(GeocodeLocationJob::class, 1);
});

it('dispatches GeocodeLocationJob when country changes on the related Place', function (): void {
    Queue::fake();

    $location = Location::factory()->create([
        'country' => 'Italy',
        'geolocation' => new Point(45.4642, 9.1900),
    ]);

    Queue::fake();

    $place = Place::query()->findOrFail($location->place_id);
    $place->update(['country' => 'France']);

    Queue::assertPushed(GeocodeLocationJob::class, 1);
});

// ─────────────────────────────────────────────────────────────────────────────
// Negative cases: non-address field changes must NOT dispatch the job
// ─────────────────────────────────────────────────────────────────────────────

it('does not dispatch GeocodeLocationJob when only non-address Place fields change', function (): void {
    Queue::fake();

    $location = Location::factory()->create([
        'geolocation' => new Point(45.4642, 9.1900),
    ]);

    Queue::fake();

    $place = Place::query()->findOrFail($location->place_id);
    $place->update(['postcode' => '99999']);

    Queue::assertNotPushed(GeocodeLocationJob::class);
});

it('does not dispatch GeocodeLocationJob when only Location non-bridged fields change', function (): void {
    Queue::fake();

    $location = Location::factory()->create([
        'geolocation' => new Point(45.4642, 9.1900),
    ]);

    Queue::fake();

    // Update only non-bridged fields on Location (name, slug are stored directly on locations table)
    Location::query()->where('id', $location->id)->update([
        'name' => 'New Name ' . uniqid(),
        'slug' => 'new-name-' . uniqid(),
    ]);

    Queue::assertNotPushed(GeocodeLocationJob::class);
});
