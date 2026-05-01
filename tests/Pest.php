<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Entity;
use Modules\CMS\Models\Pivot\Presettable;
use Modules\CMS\Models\Preset;
use Modules\Core\Casts\FieldType as CoreFieldType;
use Modules\Core\Models\Field;
use Modules\Core\Services\DynamicContentsService;
use Modules\Core\Services\PresetVersioningService;

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
function setupCMSEntities(array $entityTypes = [EntityType::CONTENTS, EntityType::CONTRIBUTORS, EntityType::CATEGORIES]): void
{
    foreach ($entityTypes as $entityType) {
        $name = match ($entityType) {
            EntityType::CONTENTS => 'contents',
            EntityType::CONTRIBUTORS => 'contributors',
            default => mb_strtolower($entityType->value),
        };

        $entity = Entity::query()->firstOrCreate(
            ['name' => $name],
            [
                'type' => $entityType,
                'slug' => Str::slug($name),
            ],
        );

        $preset = Preset::query()->firstOrCreate(['entity_id' => $entity->id, 'name' => 'default'], ['entity_id' => $entity->id, 'name' => 'default']);

        if ($preset->fields()->count() === 0) {
            $field = Field::query()->create([
                'name' => 'description_' . uniqid(),
                'type' => CoreFieldType::TEXT,
                'options' => new stdClass(),
            ]);
            $preset->fields()->attach($field->id, [
                'default' => null,
                'is_required' => false,
            ]);
        }

        $presettable = Presettable::query()
            ->where('entity_id', $entity->id)
            ->where('preset_id', $preset->id)
            ->whereNull('deleted_at')
            ->latest('version')
            ->first();

        if ($presettable === null || empty($presettable->fields_snapshot)) {
            resolve(PresetVersioningService::class)->createVersion($preset);
            DynamicContentsService::getInstance()->clearAllCaches();
        }
    }
}

/**
 * Create a test content with all required relationships.
 */
function createTestContent(array $attributes = []): Modules\CMS\Models\Content
{
    setupCMSEntities([EntityType::CONTENTS, EntityType::CONTRIBUTORS]);

    $content = Modules\CMS\Models\Content::factory()->create($attributes);

    if (! isset($attributes['category_id'])) {
        $category = Modules\CMS\Models\Category::factory()->create();
        $content->categories()->attach($category);
    }

    if (! isset($attributes['contributor_id'])) {
        $contributor = Modules\CMS\Models\Contributor::factory()->create();
        $content->contributors()->attach($contributor);
    }

    return $content;
}

/**
 * Create a test location with coordinates.
 */
function createTestLocation(array $attributes = []): Modules\CMS\Models\Location
{
    $defaults = [
        'address' => 'Milan, Italy',
    ];

    if (! isset($attributes['geolocation']) && ! isset($attributes['latitude'])) {
        $defaults['geolocation'] = new Point(45.4642, 9.1900);
    }

    return Modules\CMS\Models\Location::factory()->create(array_merge($defaults, $attributes));
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
