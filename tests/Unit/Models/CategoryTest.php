<?php

declare(strict_types=1);

use Modules\Cms\Models\Category;

it('category model has correct structure', function (): void {
    $reflection = new ReflectionClass(Category::class);
    $source = file_get_contents($reflection->getFileName());

    // Test fillable attributes
    expect($source)->toContain('protected $fillable');

    // Test hidden attributes
    expect($source)->toContain('protected $hidden');
});

it('category model uses correct traits', function (): void {
    $reflection = new ReflectionClass(Category::class);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain('Illuminate\\Database\\Eloquent\\Factories\\HasFactory');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasActivation');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasApprovals');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasMultimedia');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasPath');
    expect($traits)->toContain('Staudenmeir\\LaravelAdjacencyList\\Eloquent\\HasRecursiveRelationships');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasTags');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasTranslatedDynamicContents');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasValidations');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasValidity');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasVersions');
    expect($traits)->toContain('Modules\\Core\\Helpers\\SoftDeletes');
    expect($traits)->toContain('Modules\\Core\\Helpers\\SortableTrait');
    expect($traits)->toContain('Modules\\Core\\Locking\\Traits\\HasLocks');
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
    expect($method->getReturnType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany');
});

it('category model has correct method signatures', function (): void {
    $reflection = new ReflectionClass(Category::class);

    // Test getRules method
    $method = $reflection->getMethod('getRules');
    expect($method->getReturnType()->getName())->toBe('array');

    // Test getPath method
    $method = $reflection->getMethod('getPath');
    expect($method->getReturnType()->getName())->toBe('string');

    // Test appendPaths method
    $method = $reflection->getMethod('appendPaths');
    expect($method->getReturnType()->getName())->toBe(Category::class);

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
    $attributesProperty->setAccessible(true);
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
    $attributesProperty->setAccessible(true);
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
