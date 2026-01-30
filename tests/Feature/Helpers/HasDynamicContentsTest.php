<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Models\Author;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Field;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Helper function to create test entity with fields.
 * This function must be called within a test context where the database is ready.
 */
function createTestEntityWithFields(): array
{
    // Create a test entity with fields using create() instead of factory
    // to avoid database connection issues during factory creation
    $entity = Entity::create([
        'name' => 'Test Entity ' . uniqid(),
        'type' => EntityType::CONTENTS,
    ]);

    // Create fields with different types (Field doesn't have factory, use create directly)
    $textField = Field::create([
        'name' => 'text_field_' . uniqid(),
        'type' => FieldType::TEXT,
        'options' => new stdClass(),
    ]);

    $arrayField = Field::create([
        'name' => 'array_field_' . uniqid(),
        'type' => FieldType::ARRAY,
        'options' => new stdClass(),
    ]);

    $objectField = Field::create([
        'name' => 'object_field_' . uniqid(),
        'type' => FieldType::OBJECT,
        'options' => new stdClass(),
    ]);

    $editorField = Field::create([
        'name' => 'editor_field_' . uniqid(),
        'type' => FieldType::EDITOR,
        'options' => new stdClass(),
    ]);

    // Attach fields to entity
    $entity->fields()->attach([
        $textField->id => ['default' => null, 'required' => false],
        $arrayField->id => ['default' => null, 'required' => false],
        $objectField->id => ['default' => null, 'required' => false],
        $editorField->id => ['default' => null, 'required' => false],
    ]);

    return [
        'entity' => $entity,
        'textField' => $textField,
        'arrayField' => $arrayField,
        'objectField' => $objectField,
        'editorField' => $editorField,
    ];
}

describe('HasTranslatedDynamicContents', function (): void {
    it('removes components from fillable when using HasTranslatedDynamicContents', function (): void {
        // Create instance using factory to ensure database is ready
        $author = Author::factory()->make();
        $author->initializeHasDynamicContents();
        $author->initializeHasTranslations();
        $author->initializeHasTranslatedDynamicContents();

        expect($author->getFillable())->not->toContain('components');
        expect($author->attributes)->not->toHaveKey('components');
    });

    it('saves components in translations table when using HasTranslatedDynamicContents', function (): void {
        ['entity' => $entity] = createTestEntityWithFields();
        $author = Author::factory()->create();
        $default_locale = config('app.locale');

        $author->entity_id = $entity->id;
        $author->save();

        $components = [
            'text_field' => 'Test Text',
            'array_field' => ['item1', 'item2'],
            'object_field' => new stdClass(),
            'editor_field' => ['blocks' => []],
        ];

        $author->setTranslation($default_locale, [
            'name' => 'Test Author',
            'components' => $components,
        ]);
        $author->save();

        // Verify components are saved in translations table, not in authors table
        $translation = DB::table('author_translations')
            ->where('author_id', $author->id)
            ->where('locale', $default_locale)
            ->first();

        expect($translation)->not->toBeNull();
        expect(json_decode($translation->components, true))->toBeArray();

        // Verify components are NOT in authors table
        $authorRecord = DB::table('authors')->where('id', $author->id)->first();
        expect($authorRecord)->not->toHaveProperty('components');
    });

    it('can access dynamic content fields transparently with HasTranslatedDynamicContents', function (): void {
        ['entity' => $entity] = createTestEntityWithFields();
        $author = Author::factory()->create();
        $default_locale = config('app.locale');

        $author->entity_id = $entity->id;
        $author->save();

        $author->setTranslation($default_locale, [
            'name' => 'Test Author',
            'components' => [
                'text_field' => 'Test Text',
                'array_field' => ['item1', 'item2'],
            ],
        ]);
        $author->save();

        // Access as property
        expect($author->text_field)->toBe('Test Text');
        expect($author->array_field)->toBe(['item1', 'item2']);
    });
});

describe('mergeComponentsValues', function (): void {
    it('ensures ARRAY fields have array default value instead of null', function (): void {
        ['entity' => $entity] = createTestEntityWithFields();
        $author = Author::factory()->create();
        $author->entity_id = $entity->id;
        $author->save();

        $default_locale = config('app.locale');
        $author->setTranslation($default_locale, [
            'name' => 'Test Author',
            'components' => [], // Empty components
        ]);
        $author->save();

        // array_field should have [] as default, not null
        $components = $author->getComponentsAttribute();
        expect($components['array_field'])->toBeArray();
        expect($components['array_field'])->toBe([]);
    });

    it('ensures OBJECT fields have object default value instead of null', function (): void {
        ['entity' => $entity] = createTestEntityWithFields();
        $author = Author::factory()->create();
        $author->entity_id = $entity->id;
        $author->save();

        $default_locale = config('app.locale');
        $author->setTranslation($default_locale, [
            'name' => 'Test Author',
            'components' => [], // Empty components
        ]);
        $author->save();

        // object_field should have stdClass() as default, not null
        $components = $author->getComponentsAttribute();
        expect($components['object_field'])->toBeInstanceOf(stdClass::class);
    });

    it('ensures EDITOR fields have array default value instead of null', function (): void {
        ['entity' => $entity] = createTestEntityWithFields();
        $author = Author::factory()->create();
        $author->entity_id = $entity->id;
        $author->save();

        $default_locale = config('app.locale');
        $author->setTranslation($default_locale, [
            'name' => 'Test Author',
            'components' => [], // Empty components
        ]);
        $author->save();

        // editor_field should have ['blocks' => []] as default, not null
        $components = $author->getComponentsAttribute();
        expect($components['editor_field'])->toBeArray();
        expect($components['editor_field'])->toHaveKey('blocks');
        expect($components['editor_field']['blocks'])->toBe([]);
    });
});

describe('Validation', function (): void {
    it('validates ARRAY fields correctly', function (): void {
        ['entity' => $entity] = createTestEntityWithFields();
        $author = Author::factory()->create();
        $author->entity_id = $entity->id;
        $author->save();

        $default_locale = config('app.locale');

        // Should pass validation with array value
        $author->setTranslation($default_locale, [
            'name' => 'Test Author',
            'components' => [
                'array_field' => ['item1', 'item2'],
            ],
        ]);

        expect(fn () => $author->validateWithRules('create'))->not->toThrow(Exception::class);
    });

    it('validates OBJECT fields correctly by converting to JSON string', function (): void {
        ['entity' => $entity] = createTestEntityWithFields();
        $author = Author::factory()->create();
        $author->entity_id = $entity->id;
        $author->save();

        $default_locale = config('app.locale');

        // Should pass validation with object value (converted to JSON string)
        $author->setTranslation($default_locale, [
            'name' => 'Test Author',
            'components' => [
                'object_field' => new stdClass(),
            ],
        ]);

        expect(fn () => $author->validateWithRules('create'))->not->toThrow(Exception::class);
    });

    it('validates EDITOR fields correctly by converting to JSON string', function (): void {
        ['entity' => $entity] = createTestEntityWithFields();
        $author = Author::factory()->create();
        $author->entity_id = $entity->id;
        $author->save();

        $default_locale = config('app.locale');

        // Should pass validation with editor value (converted to JSON string)
        $author->setTranslation($default_locale, [
            'name' => 'Test Author',
            'components' => [
                'editor_field' => ['blocks' => []],
            ],
        ]);

        expect(fn () => $author->validateWithRules('create'))->not->toThrow(Exception::class);
    });

    it('fails validation when ARRAY field is not an array', function (): void {
        ['entity' => $entity] = createTestEntityWithFields();
        $author = Author::factory()->create();
        $author->entity_id = $entity->id;
        $author->save();

        $default_locale = config('app.locale');

        // Set array_field as string instead of array
        $author->setTranslation($default_locale, [
            'name' => 'Test Author',
            'components' => [
                'array_field' => 'not an array',
            ],
        ]);

        expect(fn () => $author->validateWithRules('create'))->toThrow(Illuminate\Validation\ValidationException::class);
    });
});

describe('initializeHasTranslatedDynamicContents', function (): void {
    it('removes components from fillable when called', function (): void {
        // Create instance using factory to ensure database is ready
        $author = Author::factory()->make();

        // initializeHasDynamicContents is called automatically and adds components
        // initializeHasTranslatedDynamicContents should remove it
        // Note: In Laravel, initialize methods are called automatically, but the order
        // may vary. We verify that initializeHasTranslatedDynamicContents works correctly
        $author->initializeHasTranslatedDynamicContents();

        $fillable = $author->getFillable();

        // components should NOT be in fillable after initializeHasTranslatedDynamicContents
        expect($fillable)->not->toContain('components');

        // Also verify it's not in attributes
        $reflection = new ReflectionClass($author);
        $attributesProperty = $reflection->getProperty('attributes');
        $attributes = $attributesProperty->getValue($author);

        expect($attributes)->not->toHaveKey('components');
    });

    it('removes components from attributes after HasDynamicContents adds it', function (): void {
        // Create instance using factory to ensure database is ready
        $author = Author::factory()->make();

        // Simulate what happens during initialization
        $author->initializeHasDynamicContents();
        expect($author->attributes)->toHaveKey('components');

        // Now initializeHasTranslatedDynamicContents should remove it
        $author->initializeHasTranslatedDynamicContents();
        expect($author->attributes)->not->toHaveKey('components');
    });
});

describe('Integration with HasTranslations', function (): void {
    it('components is a translatable field when using HasTranslatedDynamicContents', function (): void {
        $author = Author::factory()->create();
        $translatable_fields = $author::getTranslatableFields();

        expect($translatable_fields)->toContain('components');
    });

    it('can set components for different locales', function (): void {
        ['entity' => $entity] = createTestEntityWithFields();
        $author = Author::factory()->create();
        $author->entity_id = $entity->id;
        $author->save();

        $default_locale = config('app.locale');

        $author->setTranslation($default_locale, [
            'name' => 'Italian Author',
            'components' => [
                'text_field' => 'Testo Italiano',
            ],
        ]);

        $author->setTranslation('en', [
            'name' => 'English Author',
            'components' => [
                'text_field' => 'English Text',
            ],
        ]);
        $author->save();

        // Access with default locale
        expect($author->text_field)->toBe('Testo Italiano');

        // Access with English locale
        $enTranslation = $author->getTranslation('en');
        expect($enTranslation->components['text_field'])->toBe('English Text');
    });
});
