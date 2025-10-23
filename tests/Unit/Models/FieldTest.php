<?php

declare(strict_types=1);

use Modules\Cms\Models\Field;

test('field model has correct structure', function (): void {
    $reflection = new ReflectionClass(Field::class);
    $source = file_get_contents($reflection->getFileName());

    // Test fillable attributes
    expect($source)->toContain('protected $fillable');

    // Test hidden attributes
    expect($source)->toContain('protected $hidden');
});

test('field model uses correct traits', function (): void {
    $reflection = new ReflectionClass(Field::class);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain('Illuminate\\Database\\Eloquent\\Factories\\HasFactory');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasPath');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasSlug');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasValidations');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasVersions');
    expect($traits)->toContain('Modules\\Core\\Helpers\\SoftDeletes');
    expect($traits)->toContain('Modules\\Core\\Locking\\Traits\\HasLocks');
});

test('field model has required methods', function (): void {
    $reflection = new ReflectionClass(Field::class);

    expect($reflection->hasMethod('presets'))->toBeTrue();
    expect($reflection->hasMethod('getRules'))->toBeTrue();
    expect($reflection->hasMethod('getPath'))->toBeTrue();
    expect($reflection->hasMethod('toArray'))->toBeTrue();
});

test('field model has correct relationships', function (): void {
    $reflection = new ReflectionClass(Field::class);

    // Test presets relationship
    $method = $reflection->getMethod('presets');
    expect($method->getReturnType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany');
});

test('field model has correct method signatures', function (): void {
    $reflection = new ReflectionClass(Field::class);

    // Test getRules method
    $method = $reflection->getMethod('getRules');
    expect($method->getReturnType()->getName())->toBe('array');

    // Test getPath method
    $method = $reflection->getMethod('getPath');
    expect($method->getReturnType()->getName())->toBe('string');

    // Test toArray method
    $method = $reflection->getMethod('toArray');
    expect($method->getReturnType()->getName())->toBe('array');
});
