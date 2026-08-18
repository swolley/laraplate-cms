<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Location;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    /** @var TestCase $this */
    app()->setLocale('en');
    setupCMSEntities([EntityType::Contents]);
    $this->actingAs(User::factory()->create());
});

it('returns used locations with content counts over the map endpoint', function (): void {
    /** @var TestCase $this */
    $milan = Location::factory()->create(['name' => 'Milano', 'geolocation' => new Point(45.4642, 9.19)]);
    Location::factory()->create(['name' => 'Unused', 'geolocation' => new Point(10.0, 10.0)]);

    $content = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $content->locations()->sync([$milan->id]);

    $response = $this->getJson(route('cms.insights.map-locations'));

    $response->assertOk()
        ->assertJsonPath('data.0.id', $milan->id)
        ->assertJsonPath('data.0.contentCount', 1)
        ->assertJsonCount(1, 'data');
});

it('scopes the map endpoint to a bounding box', function (): void {
    /** @var TestCase $this */
    $milan = Location::factory()->create(['name' => 'Milano', 'geolocation' => new Point(45.4642, 9.19)]);
    $rome = Location::factory()->create(['name' => 'Roma', 'geolocation' => new Point(41.9028, 12.4964)]);

    $content = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $content->locations()->sync([$milan->id, $rome->id]);

    $response = $this->getJson(route('cms.insights.map-locations', [
        'south' => 45.0, 'west' => 8.0, 'north' => 46.0, 'east' => 10.0,
    ]));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $milan->id);
});

it('rejects a partial bounding box', function (): void {
    /** @var TestCase $this */
    $this->getJson(route('cms.insights.map-locations', ['south' => 45.0]))
        ->assertStatus(422);
});

it('returns the tag co-occurrence graph over the endpoint', function (): void {
    /** @var TestCase $this */
    $cinema = Tag::factory()->create();
    $cinema->translations()->where('locale', 'en')->update(['name' => 'Cinema']);
    $drama = Tag::factory()->create();
    $drama->translations()->where('locale', 'en')->update(['name' => 'Drama']);

    $content = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $content->tags()->sync([$cinema->id, $drama->id]);

    $response = $this->getJson(route('cms.insights.tag-graph'));

    $response->assertOk()
        ->assertJsonCount(2, 'data.nodes')
        ->assertJsonCount(1, 'data.edges')
        ->assertJsonPath('data.edges.0.weight', 1);
});
