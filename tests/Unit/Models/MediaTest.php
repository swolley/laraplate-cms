<?php

declare(strict_types=1);

use Modules\Cms\Models\Media;

it('media model has correct structure', static function (): void {
    $reflection = new ReflectionClass(Media::class);
    $source = file_get_contents($reflection->getFileName());

    // Test fillable attributes
    expect($source)->toContain('protected $fillable');

    // Test hidden attributes
    expect($source)->toContain('protected $hidden');
});

it('media model uses correct traits', static function (): void {
    $reflection = new ReflectionClass(Media::class);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain('Illuminate\\Database\\Eloquent\\Factories\\HasFactory');
    expect($traits)->toContain('Modules\\Core\\Helpers\\SoftDeletes');
});

it('media model has required methods', static function (): void {
    $reflection = new ReflectionClass(Media::class);

    expect($reflection->hasMethod('toArray'))->toBeTrue();
});

it('media model has correct method signatures', static function (): void {
    $reflection = new ReflectionClass(Media::class);

    // Test toArray method
    $method = $reflection->getMethod('toArray');
    expect($method->getReturnType()->getName())->toBe('array');
});
