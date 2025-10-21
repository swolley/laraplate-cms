<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->category = Category::factory()->create();
});

it('can be created with factory', function (): void {
    expect($this->category)->toBeInstanceOf(Category::class);
    expect($this->category->id)->not->toBeNull();
});

it('has fillable attributes', function (): void {
    $categoryData = [
        'name' => 'Technology',
        'slug' => 'technology',
        'description' => 'Technology category',
        'is_active' => true,
    ];

    $category = Category::create($categoryData);

    expectModelAttributes($category, [
        'name' => 'Technology',
        'slug' => 'technology',
        'description' => 'Technology category',
        'is_active' => true,
    ]);
});

it('belongs to many contents', function (): void {
    $content1 = Content::factory()->create(['title' => 'Article 1']);
    $content2 = Content::factory()->create(['title' => 'Article 2']);
    
    $this->category->contents()->attach([$content1->id, $content2->id]);

    expect($this->category->contents)->toHaveCount(2);
    expect($this->category->contents->pluck('title')->toArray())->toContain('Article 1', 'Article 2');
});

it('has recursive relationships for parent-child categories', function (): void {
    $parentCategory = Category::factory()->create(['name' => 'Technology']);
    $childCategory = Category::factory()->create(['name' => 'Programming']);
    
    $childCategory->parent_id = $parentCategory->id;
    $childCategory->save();

    expect($childCategory->parent)->toBeInstanceOf(Category::class);
    expect($childCategory->parent->id)->toBe($parentCategory->id);
    expect($parentCategory->children)->toHaveCount(1);
    expect($parentCategory->children->first()->id)->toBe($childCategory->id);
});

it('has slug trait', function (): void {
    expect($this->category)->toHaveMethod('generateSlug');
    expect($this->category)->toHaveMethod('getSlug');
});

it('has tags trait', function (): void {
    expect($this->category)->toHaveMethod('attachTags');
    expect($this->category)->toHaveMethod('detachTags');
});

it('has multimedia trait', function (): void {
    expect($this->category)->toHaveMethod('addMedia');
    expect($this->category)->toHaveMethod('getMedia');
});

it('has dynamic contents trait', function (): void {
    expect($this->category)->toHaveMethod('getDynamicContents');
    expect($this->category)->toHaveMethod('setDynamicContents');
});

it('has path trait', function (): void {
    expect($this->category)->toHaveMethod('getPath');
    expect($this->category)->toHaveMethod('setPath');
});

it('has approvals trait', function (): void {
    expect($this->category)->toHaveMethod('approve');
    expect($this->category)->toHaveMethod('reject');
});

it('has validity trait', function (): void {
    expect($this->category)->toHaveMethod('isValid');
    expect($this->category)->toHaveMethod('isExpired');
});

it('has versions trait', function (): void {
    expect($this->category)->toHaveMethod('versions');
    expect($this->category)->toHaveMethod('createVersion');
});

it('has soft deletes trait', function (): void {
    $this->category->delete();
    
    expect($this->category->trashed())->toBeTrue();
    expect(Category::withTrashed()->find($this->category->id))->not->toBeNull();
});

it('has sortable trait', function (): void {
    expect($this->category)->toHaveMethod('moveOrder');
    expect($this->category)->toHaveMethod('getOrder');
});

it('has locks trait', function (): void {
    expect($this->category)->toHaveMethod('lock');
    expect($this->category)->toHaveMethod('unlock');
});

it('has validations trait', function (): void {
    expect($this->category)->toHaveMethod('getRules');
});

it('can be created with specific attributes', function (): void {
    $categoryData = [
        'name' => 'Science',
        'slug' => 'science',
        'description' => 'Science category',
        'is_active' => false,
    ];

    $category = Category::create($categoryData);

    expectModelAttributes($category, [
        'name' => 'Science',
        'slug' => 'science',
        'description' => 'Science category',
        'is_active' => false,
    ]);
});

it('can be found by name', function (): void {
    $category = Category::factory()->create(['name' => 'Unique Category']);
    
    $foundCategory = Category::where('name', 'Unique Category')->first();
    
    expect($foundCategory->id)->toBe($category->id);
});

it('can be found by slug', function (): void {
    $category = Category::factory()->create(['slug' => 'unique-slug']);
    
    $foundCategory = Category::where('slug', 'unique-slug')->first();
    
    expect($foundCategory->id)->toBe($category->id);
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
    
    expect($category->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($category->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('can be serialized to array', function (): void {
    $category = Category::factory()->create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'is_active' => true,
    ]);
    $categoryArray = $category->toArray();
    
    expect($categoryArray)->toHaveKey('id');
    expect($categoryArray)->toHaveKey('name');
    expect($categoryArray)->toHaveKey('slug');
    expect($categoryArray)->toHaveKey('is_active');
    expect($categoryArray)->toHaveKey('created_at');
    expect($categoryArray)->toHaveKey('updated_at');
    expect($categoryArray['name'])->toBe('Test Category');
    expect($categoryArray['slug'])->toBe('test-category');
    expect($categoryArray['is_active'])->toBeTrue();
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

it('can have parent category', function (): void {
    $parentCategory = Category::factory()->create(['name' => 'Parent']);
    $childCategory = Category::factory()->create(['name' => 'Child']);
    
    $childCategory->parent_id = $parentCategory->id;
    $childCategory->save();
    
    expect($childCategory->parent)->toBeInstanceOf(Category::class);
    expect($childCategory->parent->id)->toBe($parentCategory->id);
});

it('can have children categories', function (): void {
    $parentCategory = Category::factory()->create(['name' => 'Parent']);
    $childCategory = Category::factory()->create(['name' => 'Child']);
    
    $childCategory->parent_id = $parentCategory->id;
    $childCategory->save();
    
    expect($parentCategory->children)->toHaveCount(1);
    expect($parentCategory->children->first()->id)->toBe($childCategory->id);
});
