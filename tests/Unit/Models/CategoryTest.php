<?php

declare(strict_types=1);

use Modules\CMS\Models\Category;
use Modules\Core\Models\Taxonomy;

it('category model has correct structure', function (): void {
    $categoryReflection = new ReflectionClass(Category::class);
    expect($categoryReflection->getParentClass()?->getName())->toBe(Taxonomy::class);

    $taxonomySource = file_get_contents((new ReflectionClass(Taxonomy::class))->getFileName());
    expect($taxonomySource)->toContain('protected $fillable')
        ->and($taxonomySource)->toContain('protected $hidden');

    $categorySource = file_get_contents($categoryReflection->getFileName());
    expect($categorySource)->toContain('$this->fillable');
});

it('category model uses correct traits', function (): void {
    $traits = array_values(class_uses_recursive(Category::class));

    expect($traits)->toContain(Illuminate\Database\Eloquent\Factories\HasFactory::class);
    expect($traits)->toContain(Modules\Core\Models\Concerns\HasActivation::class);
    expect($traits)->toContain(Modules\Core\Models\Concerns\HasApprovals::class);
    expect($traits)->toContain(Modules\CMS\Helpers\HasMultimedia::class);
    expect($traits)->toContain(Modules\Core\Models\Concerns\HasPath::class);
    expect($traits)->toContain(Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships::class);
    expect($traits)->toContain(Modules\CMS\Helpers\HasTags::class);
    expect($traits)->toContain(Modules\Core\Models\Concerns\HasTranslatedDynamicContents::class);
    expect($traits)->toContain(Modules\Core\Models\Concerns\HasValidations::class);
    expect($traits)->toContain(Modules\Core\Models\Concerns\HasValidity::class);
    expect($traits)->toContain(Modules\Core\Models\Concerns\HasVersions::class);
    expect($traits)->toContain(Modules\Core\SoftDeletes\SoftDeletes::class);
    expect($traits)->toContain(Modules\Core\Models\Concerns\SortableTrait::class);
    expect($traits)->toContain(Modules\Core\Locking\Traits\HasLocks::class);
});

it('category model has required methods', function (): void {
    $reflection = new ReflectionClass(Category::class);

    expect($reflection->hasMethod('contents'))->toBeTrue();
    expect($reflection->hasMethod('getRules'))->toBeTrue();
    expect($reflection->hasMethod('getPath'))->toBeTrue();
    expect($reflection->hasMethod('appendPaths'))->toBeTrue();
    expect($reflection->hasMethod('toArray'))->toBeTrue();
});

it('category model has correct relationships', function (): void {
    $reflection = new ReflectionClass(Category::class);

    // Test contents relationship
    $method = $reflection->getMethod('contents');
    expect($method->getReturnType()->getName())->toBe(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
});

it('category model has correct method signatures', function (): void {
    $reflection = new ReflectionClass(Category::class);

    // Test getRules method
    $method = $reflection->getMethod('getRules');
    expect($method->getReturnType()->getName())->toBe('array');

    // Test getPath method
    $method = $reflection->getMethod('getPath');
    expect($method->getReturnType()->getName())->toBe('string');

    // Test appendPaths method (declared on Taxonomy with return type self)
    $method = $reflection->getMethod('appendPaths');
    expect($method->getReturnType()->getName())->toBe(Taxonomy::class);

    // Test toArray method
    $method = $reflection->getMethod('toArray');
    expect($method->getReturnType()->getName())->toBe('array');
});

it('builds path, ids and full_name correctly from ancestors', function (): void {
    if (! function_exists('app') || ! app()->bound('session')) {
        $this->markTestSkipped('Session not available, skipping path chain test.');
    }

    $reflection = new ReflectionClass(Category::class);

    /** @var Category $root */
    $root = $reflection->newInstanceWithoutConstructor();
    $attributesProperty = $reflection->getProperty('attributes');
    $attributesProperty->setValue($root, [
        'id' => 1,
        'slug' => 'root',
        'name' => 'Root',
    ]);

    /** @var Category $child */
    $child = $reflection->newInstanceWithoutConstructor();
    $attributesProperty->setValue($child, [
        'id' => 2,
        'slug' => 'child',
        'name' => 'Child',
    ]);

    /** @var Category $grandchild */
    $grandchild = $reflection->newInstanceWithoutConstructor();
    $attributesProperty->setValue($grandchild, [
        'id' => 3,
        'slug' => 'grandchild',
        'name' => 'Grandchild',
    ]);

    // Simulate ancestors relation: [immediate parent, then root]
    $grandchild->setRelation('ancestors', collect([$child, $root]));

    expect($grandchild->getPath())->toBe('root/child/grandchild')
        ->and($grandchild->ids)->toBe('1.2.3')
        ->and($grandchild->full_name)->toBe('Root > Child > Grandchild');
});

it('falls back to current node data when no ancestors are present', function (): void {
    if (! function_exists('app') || ! app()->bound('session')) {
        $this->markTestSkipped('Session not available, skipping path fallback test.');
    }

    $reflection = new ReflectionClass(Category::class);

    /** @var Category $category */
    $category = $reflection->newInstanceWithoutConstructor();
    $attributesProperty = $reflection->getProperty('attributes');
    $attributesProperty->setValue($category, [
        'id' => 10,
        'slug' => 'single',
        'name' => 'Single',
    ]);

    // No ancestors relation set
    expect($category->getPath())->toBe('single')
        ->and($category->ids)->toBe('10')
        ->and($category->full_name)->toBe('Single');
});
