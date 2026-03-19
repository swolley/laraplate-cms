<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

use MatanYadaev\EloquentSpatial\Objects\Point;

$test_stubs = [
    'Modules\\Core\\Helpers\\HasCommandUtils' => __DIR__ . '/Stubs/Core/Helpers/HasCommandUtils.php',
    'Modules\\Core\\Helpers\\HasActivation' => __DIR__ . '/Stubs/Core/Helpers/HasActivation.php',
    'Modules\\Core\\Helpers\\HasApprovals' => __DIR__ . '/Stubs/Core/Helpers/HasApprovals.php',
    'Modules\\Core\\Helpers\\HasValidations' => __DIR__ . '/Stubs/Core/Helpers/HasValidations.php',
    'Modules\\Core\\Helpers\\HasValidity' => __DIR__ . '/Stubs/Core/Helpers/HasValidity.php',
    'Modules\\Core\\Helpers\\HasVersions' => __DIR__ . '/Stubs/Core/Helpers/HasVersions.php',
    'Modules\\Core\\Helpers\\HasTranslations' => __DIR__ . '/Stubs/Core/Helpers/HasTranslations.php',
    'Modules\\Core\\Helpers\\SoftDeletes' => __DIR__ . '/Stubs/Core/Helpers/SoftDeletes.php',
    'Modules\\Core\\Helpers\\SortableTrait' => __DIR__ . '/Stubs/Core/Helpers/SortableTrait.php',
    'Modules\\Core\\Helpers\\LocaleContext' => __DIR__ . '/Stubs/Core/Helpers/LocaleContext.php',
    'Modules\\Core\\Helpers\\ResponseBuilder' => __DIR__ . '/Stubs/Core/Helpers/ResponseBuilder.php',
    'Modules\\Core\\Helpers\\MigrateUtils' => __DIR__ . '/Stubs/Core/Helpers/MigrateUtils.php',
    'Modules\\Core\\Helpers\\HasUniqueFactoryValues' => __DIR__ . '/Stubs/Core/Helpers/HasUniqueFactoryValues.php',
    'Modules\\Core\\Casts\\FilterOperator' => __DIR__ . '/Stubs/Core/Casts/FilterOperator.php',
    'Modules\\Core\\Casts\\WhereClause' => __DIR__ . '/Stubs/Core/Casts/WhereClause.php',
    'Modules\\Core\\Cache\\HasCache' => __DIR__ . '/Stubs/Core/Cache/HasCache.php',
    'Modules\\Core\\Locking\\HasOptimisticLocking' => __DIR__ . '/Stubs/Core/Locking/HasOptimisticLocking.php',
    'Modules\\Core\\Locking\\Traits\\HasLocks' => __DIR__ . '/Stubs/Core/Locking/Traits/HasLocks.php',
    'Modules\\Core\\Overrides\\Command' => __DIR__ . '/Stubs/Core/Overrides/Command.php',
    'Modules\\Core\\Overrides\\ModuleServiceProvider' => __DIR__ . '/Stubs/Core/Overrides/ModuleServiceProvider.php',
    'Modules\\Core\\Http\\Requests\\ListRequest' => __DIR__ . '/Stubs/Core/Http/Requests/ListRequest.php',
    'Modules\\Core\\Services\\Translation\\Definitions\\ITranslated' => __DIR__ . '/Stubs/Core/Services/Translation/Definitions/ITranslated.php',
    'Modules\\Core\\Search\\Traits\\Searchable' => __DIR__ . '/Stubs/Core/Search/Traits/Searchable.php',
    'Modules\\Core\\Search\\Schema\\SchemaDefinition' => __DIR__ . '/Stubs/Core/Search/Schema/SchemaDefinition.php',
    'Modules\\Core\\Search\\Schema\\FieldDefinition' => __DIR__ . '/Stubs/Core/Search/Schema/FieldDefinition.php',
    'Modules\\Core\\Search\\Schema\\FieldType' => __DIR__ . '/Stubs/Core/Search/Schema/FieldType.php',
    'Modules\\Core\\Search\\Schema\\IndexType' => __DIR__ . '/Stubs/Core/Search/Schema/IndexType.php',
    'MatanYadaev\\EloquentSpatial\\Objects\\Point' => __DIR__ . '/Stubs/Spatial/Objects/Point.php',
    'MatanYadaev\\EloquentSpatial\\Objects\\Polygon' => __DIR__ . '/Stubs/Spatial/Objects/Polygon.php',
    'MatanYadaev\\EloquentSpatial\\Traits\\HasSpatial' => __DIR__ . '/Stubs/Spatial/Traits/HasSpatial.php',
];

foreach ($test_stubs as $class_name => $stub_path) {
    if (class_exists($class_name) || trait_exists($class_name) || interface_exists($class_name)) {
        continue;
    }

    require_once $stub_path;
}

if (! function_exists('user_class')) {
    /** @return class-string<Illuminate\Contracts\Auth\Authenticatable> */
    function user_class(): string
    {
        return Modules\Cms\Tests\Support\User::class;
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
