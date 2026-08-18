<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Location;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;

uses(TestCase::class, RefreshDatabase::class);

/**
 * The map surface is served by the generic CRUD select: locations + a `contents`
 * count aggregate (a dotless main-model relation count) + the place-derived
 * coordinates, with an optional bbox filter on the place latitude/longitude. These
 * tests pin that the select payload the frontend sends returns what the map needs
 * — and guard the alias-vs-table prefix bug that used to break the count aggregate
 * on prefixed-table entities (cms_locations).
 */
beforeEach(function (): void {
    /** @var TestCase $this */
    app()->setLocale('en');
    setupCMSEntities([EntityType::Contents]);

    $role = Role::factory()->create(['name' => config('permission.roles.superadmin'), 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);
    $this->actingAs($user);
});

function map_select(?array $filters = null): Illuminate\Testing\TestResponse
{
    /** @var TestCase $test */
    $test = test();

    return $test->postJson(route('core.crud.list', ['module' => 'cms', 'entity' => 'locations']), [
        'pagination' => 500,
        'page' => 1,
        'columns' => [['name' => 'contents', 'type' => 'count']],
        ...($filters !== null ? ['filters' => $filters] : []),
    ]);
}

it('returns locations with coordinates and a contents count over the select endpoint', function (): void {
    $milan = Location::factory()->create(['name' => 'Milano', 'geolocation' => new Point(45.4642, 9.19)]);

    $content = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $content->locations()->sync([$milan->id]);

    $response = map_select();
    $response->assertOk();

    $row = collect($response->json('data'))->firstWhere('id', $milan->id);

    expect($row)->not->toBeNull()
        ->and((float) $row['latitude'])->toBe(45.4642)
        ->and((float) $row['longitude'])->toBe(9.19)
        ->and((int) $row['contents_count'])->toBe(1);
});

it('scopes locations to a bounding box via a place lat/lng between filter', function (): void {
    $milan = Location::factory()->create(['name' => 'Milano', 'geolocation' => new Point(45.4642, 9.19)]);
    $rome = Location::factory()->create(['name' => 'Roma', 'geolocation' => new Point(41.9028, 12.4964)]);

    $content = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $content->locations()->sync([$milan->id, $rome->id]);

    $response = map_select([
        'operator' => 'and',
        'filters' => [
            ['property' => 'place.latitude', 'operator' => 'between', 'value' => [45.0, 46.0]],
            ['property' => 'place.longitude', 'operator' => 'between', 'value' => [8.0, 10.0]],
        ],
    ]);
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($milan->id)
        ->and($ids)->not->toContain($rome->id);
});
