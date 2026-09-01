<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Relations that must always be loaded (Contributor->user, Location->place)
| are declared with $with, not with a closure global scope. A closure scope is
| keyed by spl_object_hash, so it cannot be lifted by name, and it re-adds the
| eager load when the query runs, silently defeating ->without(). $with is
| declarative and stays removable per query.
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    setupCMSEntities([EntityType::Contributors]);
});

dataset('always loaded relations', [
    'contributor user' => [Contributor::class, 'user'],
    'location place' => [Location::class, 'place'],
]);

it('declares the relation via $with', function (string $model, string $relation): void {
    expect(new ReflectionProperty($model, 'with')->getValue(new $model))->toContain($relation);
})->with('always loaded relations');

it('eager loads the relation by default', function (string $model, string $relation): void {
    $model::factory()->create();

    expect($model::query()->first()->relationLoaded($relation))->toBeTrue();
})->with('always loaded relations');

it('lets a caller opt out with without()', function (string $model, string $relation): void {
    $model::factory()->create();

    expect($model::query()->without($relation)->first()->relationLoaded($relation))->toBeFalse();
})->with('always loaded relations');
