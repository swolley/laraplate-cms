<?php

declare(strict_types=1);

use MatanYadaev\EloquentSpatial\Objects\Point;

if (! function_exists('user_class')) {
    /** @return class-string<\Illuminate\Contracts\Auth\Authenticatable> */
    function user_class(): string
    {
        return \Modules\Cms\Tests\Support\User::class;
    }
}

use Modules\Cms\Casts\EntityType;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Field;
use Modules\Cms\Models\Pivot\Presettable;
use Modules\Cms\Models\Preset;

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Setup required Entity, Preset, Presettable and Field records for CMS factories.
 *
 * @param  list<EntityType>  $entityTypes
 */
function setupCmsEntities(array $entityTypes = [EntityType::CONTENTS, EntityType::CONTRIBUTORS, EntityType::CATEGORIES]): void
{
    foreach ($entityTypes as $entityType) {
        $name = match ($entityType) {
            EntityType::CONTENTS => 'contents',
            EntityType::CONTRIBUTORS => 'contributors',
            default => mb_strtolower($entityType->value),
        };

        $entity = Entity::firstOrCreate(
            ['name' => $name],
            ['type' => $entityType],
        );

        $preset = Preset::firstOrCreate(
            ['entity_id' => $entity->id, 'name' => 'default'],
            ['entity_id' => $entity->id, 'name' => 'default'],
        );

        Presettable::firstOrCreate(
            ['entity_id' => $entity->id, 'preset_id' => $preset->id],
            ['entity_id' => $entity->id, 'preset_id' => $preset->id],
        );

        if ($preset->fields()->count() === 0) {
            $field = Field::create([
                'name' => 'description_' . uniqid(),
                'type' => FieldType::TEXT,
                'options' => new stdClass(),
            ]);
            $preset->fields()->attach($field->id, [
                'default' => null,
                'is_required' => false,
            ]);
        }
    }
}

/**
 * Create a test content with all required relationships.
 */
function createTestContent(array $attributes = []): Modules\Cms\Models\Content
{
    setupCmsEntities([EntityType::CONTENTS, EntityType::CONTRIBUTORS]);

    $content = Modules\Cms\Models\Content::factory()->create($attributes);

    if (! isset($attributes['category_id'])) {
        $category = Modules\Cms\Models\Category::factory()->create();
        $content->categories()->attach($category);
    }

    if (! isset($attributes['contributor_id'])) {
        $contributor = Modules\Cms\Models\Contributor::factory()->create();
        $content->contributors()->attach($contributor);
    }

    return $content;
}

/**
 * Create a test location with coordinates.
 */
function createTestLocation(array $attributes = []): Modules\Cms\Models\Location
{
    $defaults = [
        'address' => 'Milan, Italy',
    ];

    if (! isset($attributes['geolocation']) && ! isset($attributes['latitude'])) {
        $defaults['geolocation'] = new Point(45.4642, 9.1900);
    }

    return Modules\Cms\Models\Location::factory()->create(array_merge($defaults, $attributes));
}

/**
 * Assert that a content has the expected relationships.
 */
function expectContentRelationships($content, array $relationships): void
{
    foreach ($relationships as $relation => $expectedCount) {
        expect($content->{$relation}()->count())->toBe($expectedCount);
    }
}

/**
 * Assert that a model has the expected attributes.
 */
function expectModelAttributes($model, array $attributes): void
{
    foreach ($attributes as $key => $value) {
        expect($model->{$key})->toBe($value);
    }
}

/**
 * Assert that a model exists in database with given attributes.
 */
function assertModelExists(string $modelClass, array $attributes): void
{
    expect($modelClass::where($attributes)->exists())->toBeTrue();
}

/**
 * Assert that a model does not exist in database with given attributes.
 */
function assertModelNotExists(string $modelClass, array $attributes): void
{
    expect($modelClass::where($attributes)->exists())->toBeFalse();
}
