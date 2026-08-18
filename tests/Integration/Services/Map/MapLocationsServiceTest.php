<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Location;
use Modules\CMS\Services\Map\BoundingBox;
use Modules\CMS\Services\Map\MapLocationsService;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCMSEntities([EntityType::Contents]);
});

function map_content(): Content
{
    return Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
}

function map_location_at(string $name, float $lat, float $lng): Location
{
    return Location::factory()->create([
        'name' => $name,
        'geolocation' => new Point($lat, $lng),
    ]);
}

it('returns only geo-located locations that are used in contents, with content counts', function (): void {
    $milan = map_location_at('Milano', 45.4642, 9.1900);
    $rome = map_location_at('Roma', 41.9028, 12.4964);
    map_location_at('Unused', 40.0, 10.0); // geo-located but never attached → excluded.

    $c1 = map_content();
    $c2 = map_content();
    $c1->locations()->sync([$milan->id, $rome->id]);
    $c2->locations()->sync([$milan->id]);

    $rows = app(MapLocationsService::class)->usedLocations();

    $byId = $rows->keyBy('id');

    expect($rows)->toHaveCount(2)
        ->and($byId[$milan->id]['contentCount'])->toBe(2)
        ->and($byId[$milan->id]['latitude'])->toBe(45.4642)
        ->and($byId[$milan->id]['longitude'])->toBe(9.19)
        ->and($byId[$rome->id]['contentCount'])->toBe(1)
        ->and($byId->has('Unused'))->toBeFalse();
});

it('restricts results to the given bounding box', function (): void {
    $milan = map_location_at('Milano', 45.4642, 9.1900);
    $rome = map_location_at('Roma', 41.9028, 12.4964);

    $content = map_content();
    $content->locations()->sync([$milan->id, $rome->id]);

    // A box around northern Italy that contains Milan but not Rome.
    $bounds = new BoundingBox(south: 45.0, west: 8.0, north: 46.0, east: 10.0);

    $rows = app(MapLocationsService::class)->usedLocations($bounds);

    expect($rows->pluck('id')->all())->toBe([$milan->id]);
});

it('is empty when no locations are attached to contents', function (): void {
    map_location_at('Lonely', 45.0, 9.0);

    expect(app(MapLocationsService::class)->usedLocations())->toHaveCount(0);
});
