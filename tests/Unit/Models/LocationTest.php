<?php

declare(strict_types=1);

use Modules\Cms\Models\Location;

it('location model has correct structure', function (): void {
    $reflection = new ReflectionClass(Location::class);
    $source = file_get_contents($reflection->getFileName());

    // Test fillable attributes
    expect($source)->toContain('protected $fillable');

    // Test hidden attributes
    expect($source)->toContain('protected $hidden');
});

it('location model uses correct traits', function (): void {
    $reflection = new ReflectionClass(Location::class);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain(Illuminate\Database\Eloquent\Factories\HasFactory::class);
    expect($traits)->toContain(Modules\Cms\Helpers\HasPath::class);
    expect($traits)->toContain(Modules\Cms\Helpers\HasSlug::class);
    expect($traits)->toContain(MatanYadaev\EloquentSpatial\Traits\HasSpatial::class);
    expect($traits)->toContain(Modules\Cms\Helpers\HasTags::class);
    expect($traits)->toContain(Modules\Core\Helpers\HasValidations::class);
    expect($traits)->toContain(Modules\Core\Search\Traits\Searchable::class);
    expect($traits)->toContain(Modules\Core\Helpers\SoftDeletes::class);
});

it('location model has required methods', function (): void {
    $reflection = new ReflectionClass(Location::class);

    expect($reflection->hasMethod('contents'))->toBeTrue();
    expect($reflection->hasMethod('getRules'))->toBeTrue();
    expect($reflection->hasMethod('getPath'))->toBeTrue();
    expect($reflection->hasMethod('toArray'))->toBeTrue();
});

it('location model has correct relationships', function (): void {
    $reflection = new ReflectionClass(Location::class);

    // Test contents relationship
    $method = $reflection->getMethod('contents');
    expect($method->getReturnType()->getName())->toBe(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
});

it('location model has correct method signatures', function (): void {
    $reflection = new ReflectionClass(Location::class);

    // Test getRules method
    $method = $reflection->getMethod('getRules');
    expect($method->getReturnType()->getName())->toBe('array');

    // Test getPath method
    $method = $reflection->getMethod('getPath');
    expect($method->getReturnType()->getName())->toBe('string');

    // Test toArray method
    $method = $reflection->getMethod('toArray');
    expect($method->hasReturnType())->toBeFalse();
});
