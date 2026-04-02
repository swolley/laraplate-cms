<?php

declare(strict_types=1);

use Modules\Core\Models\Field;

it('field model has correct structure', function (): void {
    $reflection = new ReflectionClass(Field::class);
    $source = file_get_contents($reflection->getFileName());

    // Test fillable attributes
    expect($source)->toContain('protected $fillable');

    // Test hidden attributes
    expect($source)->toContain('protected $hidden');
});

it('field model uses correct traits', function (): void {
    $reflection = new ReflectionClass(Field::class);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain(Illuminate\Database\Eloquent\Factories\HasFactory::class);
    expect($traits)->toContain(Modules\Core\Helpers\HasActivation::class);
    expect($traits)->toContain(Modules\Core\Helpers\HasValidations::class);
    expect($traits)->toContain(Modules\Core\Helpers\HasVersions::class);
    expect($traits)->toContain(Modules\Core\Helpers\SoftDeletes::class);
});

it('field model has required methods', function (): void {
    $reflection = new ReflectionClass(Field::class);

    expect($reflection->hasMethod('presets'))->toBeTrue();
    expect($reflection->hasMethod('getRules'))->toBeTrue();
    expect($reflection->hasMethod('toArray'))->toBeTrue();
});

it('field model has correct relationships', function (): void {
    $reflection = new ReflectionClass(Field::class);

    // Test presets relationship
    $method = $reflection->getMethod('presets');
    expect($method->getReturnType()->getName())->toBe(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
});

it('field model has correct method signatures', function (): void {
    $reflection = new ReflectionClass(Field::class);

    // Test getRules method
    $method = $reflection->getMethod('getRules');
    expect($method->getReturnType()->getName())->toBe('array');

    // Test toArray method
    $method = $reflection->getMethod('toArray');
    expect($method->getReturnType()->getName())->toBe('array');
});
