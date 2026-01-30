<?php

declare(strict_types=1);

use Modules\Cms\Models\Preset;

it('preset model has correct structure', function (): void {
    $reflection = new ReflectionClass(Preset::class);
    $source = file_get_contents($reflection->getFileName());

    // Test fillable attributes
    expect($source)->toContain('protected $fillable');

    // Test hidden attributes
    expect($source)->toContain('protected $hidden');
});

it('preset model uses correct traits', function (): void {
    $reflection = new ReflectionClass(Preset::class);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain('Illuminate\\Database\\Eloquent\\Factories\\HasFactory');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasActivation');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasApprovals');
    expect($traits)->toContain('Modules\\Core\\Cache\\HasCache');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasValidations');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasVersions');
    expect($traits)->toContain('Modules\\Core\\Helpers\\SoftDeletes');
});

it('preset model has required methods', function (): void {
    $reflection = new ReflectionClass(Preset::class);

    expect($reflection->hasMethod('entity'))->toBeTrue();
    expect($reflection->hasMethod('fields'))->toBeTrue();
    expect($reflection->hasMethod('getRules'))->toBeTrue();
    expect($reflection->hasMethod('toArray'))->toBeTrue();
});

it('preset model has correct relationships', function (): void {
    $reflection = new ReflectionClass(Preset::class);

    // Test entity relationship
    $method = $reflection->getMethod('entity');
    expect($method->getReturnType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Relations\\BelongsTo');

    // Test fields relationship
    $method = $reflection->getMethod('fields');
    expect($method->getReturnType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany');
});

it('preset model has correct method signatures', function (): void {
    $reflection = new ReflectionClass(Preset::class);

    // Test getRules method
    $method = $reflection->getMethod('getRules');
    expect($method->getReturnType()->getName())->toBe('array');

    // Test toArray method
    $method = $reflection->getMethod('toArray');
    expect($method->getReturnType()->getName())->toBe('array');
});
