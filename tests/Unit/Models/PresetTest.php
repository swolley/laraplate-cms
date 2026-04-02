<?php

declare(strict_types=1);

use Modules\Cms\Models\Preset;
use Modules\Core\Models\Preset as CorePreset;

it('preset model has correct structure', function (): void {
    $reflection = new ReflectionClass(Preset::class);
    $parent = $reflection->getParentClass();
    expect($parent)->not->toBeNull();
    $source = file_get_contents((string) $parent->getFileName());

    expect($source)->toContain('protected $fillable')
        ->and($source)->toContain('protected $hidden');
});

it('preset model uses correct traits', function (): void {
    $traits = array_values(class_uses_recursive(CorePreset::class));

    expect($traits)->toContain(Illuminate\Database\Eloquent\Factories\HasFactory::class);
    expect($traits)->toContain(Modules\Core\Helpers\HasActivation::class);
    expect($traits)->toContain(Modules\Core\Helpers\HasApprovals::class);
    expect($traits)->toContain(Modules\Core\Cache\HasCache::class);
    expect($traits)->toContain(Modules\Core\Helpers\HasValidations::class);
    expect($traits)->toContain(Modules\Core\Helpers\HasVersions::class);
    expect($traits)->toContain(Modules\Core\Helpers\SoftDeletes::class);
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
    expect($method->getReturnType()->getName())->toBe(Illuminate\Database\Eloquent\Relations\BelongsTo::class);

    // Test fields relationship
    $method = $reflection->getMethod('fields');
    expect($method->getReturnType()->getName())->toBe(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
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
