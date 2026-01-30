<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Field;
use Modules\Cms\Models\Pivot\Presettable;
use Modules\Cms\Models\Preset;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    // Create Entity, Preset, Presettable, and Field required for Category factory
    $entity = Entity::firstOrCreate(
        ['name' => 'categories'],
        ['type' => EntityType::CATEGORIES],
    );

    $preset = Preset::firstOrCreate(
        ['entity_id' => $entity->id, 'name' => 'default'],
        ['entity_id' => $entity->id, 'name' => 'default'],
    );

    // Presettable might be created automatically by triggers, so use firstOrCreate
    Presettable::firstOrCreate(
        ['entity_id' => $entity->id, 'preset_id' => $preset->id],
        ['entity_id' => $entity->id, 'preset_id' => $preset->id],
    );

    // Create at least one Field for the Preset (required by fillDynamicContents)
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

    $this->category = Category::factory()->create();
});

it('can be created with factory', function (): void {
    expect($this->category)->toBeInstanceOf(Category::class);
    expect($this->category->id)->not->toBeNull();
});

it('has translatable attributes', function (): void {
    $category = Category::factory()->create();

    // Set translation for default locale
    $default_locale = config('app.locale');
    $category->setTranslation($default_locale, [
        'name' => 'Technology',
        'slug' => 'technology',
        'components' => [
            'description' => 'Technology category',
        ],
    ]);
    $category->save();

    expect($category->name)->toBe('Technology');
    expect($category->slug)->toBe('technology');
    expect($category->description)->toBe('Technology category');
});

it('belongs to many contents', function (): void {
    $content1 = Content::factory()->create();
    $content1->setTranslation(config('app.locale'), ['title' => 'Article 1']);
    $content1->save();

    $content2 = Content::factory()->create();
    $content2->setTranslation(config('app.locale'), ['title' => 'Article 2']);
    $content2->save();

    $this->category->contents()->attach([$content1->id, $content2->id]);

    expect($this->category->contents)->toHaveCount(2);
    expect($this->category->contents->pluck('title')->toArray())->toContain('Article 1', 'Article 2');
});

it('has recursive relationships for parent-child categories', function (): void {
    $parentCategory = Category::factory()->create();
    $parentCategory->setTranslation(config('app.locale'), ['name' => 'Technology']);
    $parentCategory->save();

    $childCategory = Category::factory()->create();
    $childCategory->setTranslation(config('app.locale'), ['name' => 'Programming']);
    $childCategory->parent_id = $parentCategory->id;
    $childCategory->save();

    expect($childCategory->parent)->toBeInstanceOf(Category::class);
    expect($childCategory->parent->id)->toBe($parentCategory->id);
    expect($parentCategory->children)->toHaveCount(1);
    expect($parentCategory->children->first()->id)->toBe($childCategory->id);
});

it('has slug trait', function (): void {
    expect(method_exists($this->category, 'generateSlug'))->toBeTrue();
    expect(method_exists($this->category, 'getSlug'))->toBeTrue();
});

it('has tags trait', function (): void {
    expect(method_exists($this->category, 'attachTags'))->toBeTrue();
    expect(method_exists($this->category, 'detachTags'))->toBeTrue();
});

it('has multimedia trait', function (): void {
    expect(method_exists($this->category, 'addMedia'))->toBeTrue();
    expect(method_exists($this->category, 'getMedia'))->toBeTrue();
});

it('has dynamic contents trait', function (): void {
    expect(method_exists($this->category, 'getDynamicContents'))->toBeTrue();
    expect(method_exists($this->category, 'setDynamicContents'))->toBeTrue();
});

it('has path trait', function (): void {
    expect(method_exists($this->category, 'getPath'))->toBeTrue();
    expect(method_exists($this->category, 'setPath'))->toBeTrue();
});

it('has approvals trait', function (): void {
    expect(method_exists($this->category, 'approve'))->toBeTrue();
    expect(method_exists($this->category, 'reject'))->toBeTrue();
});

it('has validity trait', function (): void {
    expect(method_exists($this->category, 'isValid'))->toBeTrue();
    expect(method_exists($this->category, 'isExpired'))->toBeTrue();
});

it('has versions trait', function (): void {
    expect(method_exists($this->category, 'versions'))->toBeTrue();
    expect(method_exists($this->category, 'createVersion'))->toBeTrue();
});

it('has soft deletes trait', function (): void {
    $this->category->delete();

    expect($this->category->trashed())->toBeTrue();
    expect(Category::withTrashed()->find($this->category->id))->not->toBeNull();
});

it('has sortable trait', function (): void {
    expect(method_exists($this->category, 'moveOrder'))->toBeTrue();
    expect(method_exists($this->category, 'getOrder'))->toBeTrue();
});

it('has locks trait', function (): void {
    expect(method_exists($this->category, 'lock'))->toBeTrue();
    expect(method_exists($this->category, 'unlock'))->toBeTrue();
});

it('has validations trait', function (): void {
    expect(method_exists($this->category, 'getRules'))->toBeTrue();
});

it('can be created with specific translation attributes', function (): void {
    $category = Category::factory()->create();
    $default_locale = config('app.locale');
    $category->setTranslation($default_locale, [
        'name' => 'Science',
        'slug' => 'science',
        'components' => [
            'description' => 'Science category',
        ],
    ]);
    $category->save();

    expect($category->name)->toBe('Science');
    expect($category->slug)->toBe('science');
    expect($category->description)->toBe('Science category');
});

it('can be found by name through translation', function (): void {
    $category = Category::factory()->create();
    $default_locale = config('app.locale');
    $category->setTranslation($default_locale, [
        'name' => 'Unique Category',
        'slug' => 'unique-category',
    ]);
    $category->save();

    $foundCategory = Category::whereHas('translations', static function ($q): void {
        $q->where('name', 'Unique Category');
    })->first();

    expect($foundCategory->id)->toBe($category->id);
    expect($foundCategory->name)->toBe('Unique Category');
});

it('can be found by slug through translation', function (): void {
    $category = Category::factory()->create();
    $default_locale = config('app.locale');
    $category->setTranslation($default_locale, [
        'name' => 'Test Category',
        'slug' => 'unique-slug',
    ]);
    $category->save();

    $foundCategory = Category::whereHas('translations', static function ($q): void {
        $q->where('slug', 'unique-slug');
    })->first();

    expect($foundCategory->id)->toBe($category->id);
    expect($foundCategory->slug)->toBe('unique-slug');
});

it('can be found by active status', function (): void {
    $activeCategory = Category::factory()->create(['is_active' => true]);
    $inactiveCategory = Category::factory()->create(['is_active' => false]);

    $activeCategories = Category::where('is_active', true)->get();
    $inactiveCategories = Category::where('is_active', false)->get();

    expect($activeCategories)->toHaveCount(1);
    expect($inactiveCategories)->toHaveCount(1);
    expect($activeCategories->first()->id)->toBe($activeCategory->id);
    expect($inactiveCategories->first()->id)->toBe($inactiveCategory->id);
});

it('has proper timestamps', function (): void {
    $category = Category::factory()->create();

    expect($category->created_at)->toBeInstanceOf(Carbon\CarbonImmutable::class);
    expect($category->updated_at)->toBeInstanceOf(Carbon\CarbonImmutable::class);
});

it('can be serialized to array with translations', function (): void {
    $category = Category::factory()->create();
    $default_locale = config('app.locale');
    $category->setTranslation($default_locale, [
        'name' => 'Test Category',
        'slug' => 'test-category',
    ]);
    $category->save();

    $categoryArray = $category->toArray();

    expect($categoryArray)->toHaveKey('id');
    expect($categoryArray)->toHaveKey('name');
    expect($categoryArray)->toHaveKey('slug');
    expect($categoryArray)->toHaveKey('created_at');
    expect($categoryArray)->toHaveKey('updated_at');
    expect($categoryArray['name'])->toBe('Test Category');
    expect($categoryArray['slug'])->toBe('test-category');
});

it('can be restored after soft delete', function (): void {
    $category = Category::factory()->create();
    $category->delete();

    expect($category->trashed())->toBeTrue();

    $category->restore();

    expect($category->trashed())->toBeFalse();
});

it('can be permanently deleted', function (): void {
    $category = Category::factory()->create();
    $categoryId = $category->id;

    $category->forceDelete();

    expect(Category::withTrashed()->find($categoryId))->toBeNull();
});

it('has translations trait', function (): void {
    expect(method_exists($this->category, 'translations'))->toBeTrue();
    expect(method_exists($this->category, 'translation'))->toBeTrue();
    expect(method_exists($this->category, 'getTranslation'))->toBeTrue();
    expect(method_exists($this->category, 'setTranslation'))->toBeTrue();
    expect(method_exists($this->category, 'hasTranslation'))->toBeTrue();
    expect(method_exists($this->category, 'getTranslatableFields'))->toBeTrue();
});
