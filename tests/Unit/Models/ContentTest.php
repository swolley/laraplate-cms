<?php

declare(strict_types=1);

use Modules\Cms\Models\Content;

test('content model has correct structure', function (): void {
    $reflection = new ReflectionClass(Content::class);
    $source = file_get_contents($reflection->getFileName());
    
    // Test fillable attributes
    expect($source)->toContain('protected $fillable');
    
    // Test hidden attributes
    expect($source)->toContain('protected $hidden');
});

test('content model uses correct traits', function (): void {
    $reflection = new ReflectionClass(Content::class);
    $traits = $reflection->getTraitNames();
    
    expect($traits)->toContain('Illuminate\\Database\\Eloquent\\Factories\\HasFactory');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasDynamicContents');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasMultimedia');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasPath');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasSlug');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasTags');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasApprovals');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasChildren');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasValidations');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasValidity');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasVersions');
    expect($traits)->toContain('Modules\\Core\\Helpers\\SoftDeletes');
    expect($traits)->toContain('Modules\\Core\\Helpers\\SortableTrait');
    expect($traits)->toContain('Modules\\Core\\Locking\\HasOptimisticLocking');
    expect($traits)->toContain('Modules\\Core\\Locking\\Traits\\HasLocks');
    expect($traits)->toContain('Modules\\Core\\Search\\Traits\\Searchable');
    expect($traits)->toContain('Spatie\\EloquentSortable\\Sortable');
    expect($traits)->toContain('Spatie\\MediaLibrary\\HasMedia');
});

test('content model has required methods', function (): void {
    $reflection = new ReflectionClass(Content::class);
    
    expect($reflection->hasMethod('authors'))->toBeTrue();
    expect($reflection->hasMethod('categories'))->toBeTrue();
    expect($reflection->hasMethod('tags'))->toBeTrue();
    expect($reflection->hasMethod('locations'))->toBeTrue();
    expect($reflection->hasMethod('related'))->toBeTrue();
    expect($reflection->hasMethod('toSearchableArray'))->toBeTrue();
    expect($reflection->hasMethod('getSearchMapping'))->toBeTrue();
    expect($reflection->hasMethod('getRules'))->toBeTrue();
    expect($reflection->hasMethod('getPathPrefix'))->toBeTrue();
    expect($reflection->hasMethod('getPath'))->toBeTrue();
    expect($reflection->hasMethod('toArray'))->toBeTrue();
});

test('content model has correct relationships', function (): void {
    $reflection = new ReflectionClass(Content::class);
    
    // Test authors relationship
    $method = $reflection->getMethod('authors');
    expect($method->getReturnType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany');
    
    // Test categories relationship
    $method = $reflection->getMethod('categories');
    expect($method->getReturnType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany');
    
    // Test locations relationship
    $method = $reflection->getMethod('locations');
    expect($method->getReturnType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany');
    
    // Test related relationship
    $method = $reflection->getMethod('related');
    expect($method->getReturnType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany');
});

test('content model has correct method signatures', function (): void {
    $reflection = new ReflectionClass(Content::class);
    
    // Test toSearchableArray method
    $method = $reflection->getMethod('toSearchableArray');
    expect($method->getReturnType()->getName())->toBe('array');
    
    // Test getSearchMapping method
    $method = $reflection->getMethod('getSearchMapping');
    expect($method->getReturnType()->getName())->toBe('array');
    
    // Test getRules method
    $method = $reflection->getMethod('getRules');
    expect($method->getReturnType()->getName())->toBe('array');
    
    // Test getPathPrefix method
    $method = $reflection->getMethod('getPathPrefix');
    expect($method->getReturnType()->getName())->toBe('string');
    
    // Test getPath method
    $method = $reflection->getMethod('getPath');
    expect($method->getReturnType()->getName())->toBe('string');
    
    // Test toArray method
    $method = $reflection->getMethod('toArray');
    expect($method->getReturnType()->getName())->toBe('array');
});

test('content model implements correct interfaces', function (): void {
    $reflection = new ReflectionClass(Content::class);
    
    expect($reflection->implementsInterface('Spatie\\MediaLibrary\\HasMedia'))->toBeTrue();
    expect($reflection->implementsInterface('Spatie\\EloquentSortable\\Sortable'))->toBeTrue();
});
