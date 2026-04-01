<?php

declare(strict_types=1);

use Modules\Cms\Models\Media;

it('media model has correct structure', function (): void {
    $reflection = new ReflectionClass(Media::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('protected $appends');
});

it('media model uses correct traits', function (): void {
    $reflection = new ReflectionClass(Media::class);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain(Illuminate\Database\Eloquent\Factories\HasFactory::class);
    expect($traits)->toContain(Modules\Core\Helpers\SoftDeletes::class);
});

it('media model has required methods', function (): void {
    $reflection = new ReflectionClass(Media::class);

    expect($reflection->hasMethod('toArray'))->toBeTrue();
});

it('media model has correct method signatures', function (): void {
    $reflection = new ReflectionClass(Media::class);

    // Test toArray method
    $method = $reflection->getMethod('toArray');
    expect($method->hasReturnType())->toBeFalse();
});
