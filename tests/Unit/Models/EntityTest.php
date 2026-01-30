<?php

declare(strict_types=1);

use Modules\Cms\Models\Entity;

it('entity model has correct structure', function (): void {
    $reflection = new ReflectionClass(Entity::class);
    $source = file_get_contents($reflection->getFileName());

    // Test fillable attributes
    expect($source)->toContain('protected $fillable');

    // Test hidden attributes
    expect($source)->toContain('protected $hidden');
});

it('entity model uses correct traits', function (): void {
    $reflection = new ReflectionClass(Entity::class);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain('Illuminate\\Database\\Eloquent\\Factories\\HasFactory');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasActivation');
    expect($traits)->toContain('Modules\\Core\\Cache\\HasCache');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasPath');
    expect($traits)->toContain('Modules\\Cms\\Helpers\\HasSlug');
    expect($traits)->toContain('Modules\\Core\\Helpers\\HasValidations');
    expect($traits)->toContain('Modules\\Core\\Locking\\Traits\\HasLocks');
});

it('entity model has required methods', function (): void {
    $reflection = new ReflectionClass(Entity::class);

    expect($reflection->hasMethod('presets'))->toBeTrue();
    expect($reflection->hasMethod('contents'))->toBeTrue();
    expect($reflection->hasMethod('getRules'))->toBeTrue();
    expect($reflection->hasMethod('getPath'))->toBeTrue();
    expect($reflection->hasMethod('toArray'))->toBeTrue();
});

it('entity model has correct relationships', function (): void {
    $reflection = new ReflectionClass(Entity::class);

    // Test presets relationship
    $method = $reflection->getMethod('presets');
    expect($method->getReturnType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Relations\\HasMany');

    // Test contents relationship
    $method = $reflection->getMethod('contents');
    expect($method->getReturnType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Relations\\HasManyThrough');
});

it('entity model has correct method signatures', function (): void {
    $reflection = new ReflectionClass(Entity::class);

    // Test getRules method
    $method = $reflection->getMethod('getRules');
    expect($method->getReturnType()->getName())->toBe('array');

    // Test getPath method
    $method = $reflection->getMethod('getPath');
    $returnType = $method->getReturnType();
    expect($returnType)->not->toBeNull();

    if ($returnType instanceof ReflectionNamedType) {
        expect($returnType->getName())->toBe('string');
        expect($returnType->allowsNull())->toBeTrue();
    } elseif ($returnType instanceof ReflectionUnionType) {
        $types = $returnType->getTypes();
        $typeNames = array_map(fn ($t) => $t->getName(), $types);
        expect($typeNames)->toContain('string');
    }

    // Test toArray method
    $method = $reflection->getMethod('toArray');
    $returnType = $method->getReturnType();

    if ($returnType !== null) {
        expect($returnType->getName())->toBe('array');
    }
});
