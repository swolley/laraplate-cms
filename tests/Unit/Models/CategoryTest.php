<?php

declare(strict_types=1);

use Modules\Cms\Models\Category;

test('category model has correct structure', function (): void {
    $reflection = new ReflectionClass(Category::class);
    $source = file_get_contents($reflection->getFileName());

    // Test fillable attributes
    expect($source)->toContain('protected $fillable');

    // Test hidden attributes
    expect($source)->toContain('protected $hidden');
});

test('category model uses correct traits', function (): void {
    $reflection = new ReflectionClass(Category::class);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain('Illuminate\\Database\\Eloquent\\Factories\\HasFactory');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasPath');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasSlug');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasValidations');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasVersions');
    expect($traits)->toContain('Modules\\Core\\Helpers\\SoftDeletes');
    expect($traits)->toContain('Modules\\Core\\Locking\\Traits\\HasLocks');
});

test('category model has required methods', function (): void {
    $reflection = new ReflectionClass(Category::class);

    expect($reflection->hasMethod('entity'))->toBeTrue();
    expect($reflection->hasMethod('contents'))->toBeTrue();
    expect($reflection->hasMethod('getRules'))->toBeTrue();
    expect($reflection->hasMethod('getPath'))->toBeTrue();
    expect($reflection->hasMethod('appendPaths'))->toBeTrue();
    expect($reflection->hasMethod('toArray'))->toBeTrue();
});

test('category model has correct relationships', function (): void {
    $reflection = new ReflectionClass(Category::class);

    // Test entity relationship
    $method = $reflection->getMethod('entity');
    expect($method->getReturnType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Relations\\BelongsTo');

    // Test contents relationship
    $method = $reflection->getMethod('contents');
    expect($method->getReturnType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany');
});

test('category model has correct method signatures', function (): void {
    $reflection = new ReflectionClass(Category::class);

    // Test getRules method
    $method = $reflection->getMethod('getRules');
    expect($method->getReturnType()->getName())->toBe('array');

    // Test getPath method
    $method = $reflection->getMethod('getPath');
    expect($method->getReturnType()->getName())->toBe('string');

    // Test appendPaths method
    $method = $reflection->getMethod('appendPaths');
    expect($method->getReturnType()->getName())->toBe('self');

    // Test toArray method
    $method = $reflection->getMethod('toArray');
    expect($method->getReturnType()->getName())->toBe('array');
});
