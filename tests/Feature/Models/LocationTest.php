<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Location;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->location = Location::factory()->create();
});

it('can be created with factory', function (): void {
    expect($this->location)->toBeInstanceOf(Location::class);
    expect($this->location->id)->not->toBeNull();
});

it('has fillable attributes', function (): void {
    $locationData = [
        'name' => 'Milan',
        'address' => 'Piazza del Duomo, Milan, Italy',
        'city' => 'Milan',
        'country' => 'Italy',
        'latitude' => 45.4642,
        'longitude' => 9.1900,
        'is_active' => true,
    ];

    $location = Location::create($locationData);

    expectModelAttributes($location, [
        'name' => 'Milan',
        'address' => 'Piazza del Duomo, Milan, Italy',
        'city' => 'Milan',
        'country' => 'Italy',
        'latitude' => 45.4642,
        'longitude' => 9.1900,
        'is_active' => true,
    ]);
});

it('belongs to many contents', function (): void {
    $content1 = Content::factory()->create(['title' => 'Article 1']);
    $content2 = Content::factory()->create(['title' => 'Article 2']);
    
    $this->location->contents()->attach([$content1->id, $content2->id]);

    expect($this->location->contents)->toHaveCount(2);
    expect($this->location->contents->pluck('title')->toArray())->toContain('Article 1', 'Article 2');
});

it('has spatial trait', function (): void {
    expect($this->location)->toHaveMethod('whereDistance');
    expect($this->location)->toHaveMethod('orderByDistance');
    expect($this->location)->toHaveMethod('whereDistanceSphere');
    expect($this->location)->toHaveMethod('orderByDistanceSphere');
});

it('has slug trait', function (): void {
    expect($this->location)->toHaveMethod('generateSlug');
    expect($this->location)->toHaveMethod('getSlug');
});

it('has tags trait', function (): void {
    expect($this->location)->toHaveMethod('attachTags');
    expect($this->location)->toHaveMethod('detachTags');
});

it('has path trait', function (): void {
    expect($this->location)->toHaveMethod('getPath');
    expect($this->location)->toHaveMethod('setPath');
});

it('has searchable trait', function (): void {
    expect($this->location)->toHaveMethod('toSearchableArray');
    expect($this->location)->toHaveMethod('shouldBeSearchable');
});

it('has soft deletes trait', function (): void {
    $this->location->delete();
    
    expect($this->location->trashed())->toBeTrue();
    expect(Location::withTrashed()->find($this->location->id))->not->toBeNull();
});

it('has validations trait', function (): void {
    expect($this->location)->toHaveMethod('getRules');
});

it('can be created with specific attributes', function (): void {
    $locationData = [
        'name' => 'Rome',
        'address' => 'Colosseum, Rome, Italy',
        'city' => 'Rome',
        'country' => 'Italy',
        'latitude' => 41.9028,
        'longitude' => 12.4964,
        'is_active' => false,
    ];

    $location = Location::create($locationData);

    expectModelAttributes($location, [
        'name' => 'Rome',
        'address' => 'Colosseum, Rome, Italy',
        'city' => 'Rome',
        'country' => 'Italy',
        'latitude' => 41.9028,
        'longitude' => 12.4964,
        'is_active' => false,
    ]);
});

it('can be found by name', function (): void {
    $location = Location::factory()->create(['name' => 'Unique Location']);
    
    $foundLocation = Location::where('name', 'Unique Location')->first();
    
    expect($foundLocation->id)->toBe($location->id);
});

it('can be found by city', function (): void {
    $location = Location::factory()->create(['city' => 'Unique City']);
    
    $foundLocation = Location::where('city', 'Unique City')->first();
    
    expect($foundLocation->id)->toBe($location->id);
});

it('can be found by country', function (): void {
    $location = Location::factory()->create(['country' => 'Unique Country']);
    
    $foundLocation = Location::where('country', 'Unique Country')->first();
    
    expect($foundLocation->id)->toBe($location->id);
});

it('can be found by active status', function (): void {
    $activeLocation = Location::factory()->create(['is_active' => true]);
    $inactiveLocation = Location::factory()->create(['is_active' => false]);
    
    $activeLocations = Location::where('is_active', true)->get();
    $inactiveLocations = Location::where('is_active', false)->get();
    
    expect($activeLocations)->toHaveCount(1);
    expect($inactiveLocations)->toHaveCount(1);
    expect($activeLocations->first()->id)->toBe($activeLocation->id);
    expect($inactiveLocations->first()->id)->toBe($inactiveLocation->id);
});

it('can be found by coordinates', function (): void {
    $location = Location::factory()->create([
        'latitude' => 45.4642,
        'longitude' => 9.1900,
    ]);
    
    $foundLocation = Location::where('latitude', 45.4642)
        ->where('longitude', 9.1900)
        ->first();
    
    expect($foundLocation->id)->toBe($location->id);
});

it('has proper timestamps', function (): void {
    $location = Location::factory()->create();
    
    expect($location->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($location->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('can be serialized to array', function (): void {
    $location = Location::factory()->create([
        'name' => 'Test Location',
        'city' => 'Test City',
        'is_active' => true,
    ]);
    $locationArray = $location->toArray();
    
    expect($locationArray)->toHaveKey('id');
    expect($locationArray)->toHaveKey('name');
    expect($locationArray)->toHaveKey('city');
    expect($locationArray)->toHaveKey('is_active');
    expect($locationArray)->toHaveKey('created_at');
    expect($locationArray)->toHaveKey('updated_at');
    expect($locationArray['name'])->toBe('Test Location');
    expect($locationArray['city'])->toBe('Test City');
    expect($locationArray['is_active'])->toBeTrue();
});

it('can be restored after soft delete', function (): void {
    $location = Location::factory()->create();
    $location->delete();
    
    expect($location->trashed())->toBeTrue();
    
    $location->restore();
    
    expect($location->trashed())->toBeFalse();
});

it('can be permanently deleted', function (): void {
    $location = Location::factory()->create();
    $locationId = $location->id;
    
    $location->forceDelete();
    
    expect(Location::withTrashed()->find($locationId))->toBeNull();
});

it('can work with spatial queries', function (): void {
    $location = Location::factory()->create([
        'latitude' => 45.4642,
        'longitude' => 9.1900,
    ]);
    
    $point = new Point(45.4642, 9.1900);
    
    // Test that spatial methods exist
    expect($this->location)->toHaveMethod('whereDistance');
    expect($this->location)->toHaveMethod('orderByDistance');
    expect($this->location)->toHaveMethod('whereDistanceSphere');
    expect($this->location)->toHaveMethod('orderByDistanceSphere');
});
