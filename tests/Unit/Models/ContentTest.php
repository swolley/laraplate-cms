<?php

declare(strict_types=1);

use Modules\Cms\Models\Content;

it('content model has correct structure', function (): void {
    $reflection = new ReflectionClass(Content::class);
    $source = file_get_contents($reflection->getFileName());

    // Test hidden attributes
    expect($source)->toContain('protected $hidden');
});

it('content model uses correct traits', function (): void {
    $reflection = new ReflectionClass(Content::class);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain(Illuminate\Database\Eloquent\Factories\HasFactory::class);
    expect($traits)->toContain(Modules\Cms\Helpers\HasTranslatedDynamicContents::class);
    expect($traits)->toContain(Modules\Cms\Helpers\HasMultimedia::class);
    expect($traits)->toContain(Modules\Cms\Helpers\HasPath::class);
    expect($traits)->toContain(Modules\Cms\Helpers\HasTags::class);
    expect($traits)->toContain(Modules\Core\Helpers\HasApprovals::class);
    expect($traits)->toContain(Modules\Core\Helpers\HasValidations::class);
    expect($traits)->toContain(Modules\Core\Helpers\HasValidity::class);
    expect($traits)->toContain(Modules\Core\Helpers\HasVersions::class);
    expect($traits)->toContain(Modules\Core\Helpers\SoftDeletes::class);
    expect($traits)->toContain(Modules\Core\Helpers\SortableTrait::class);
    expect($traits)->toContain(Modules\Core\Locking\HasOptimisticLocking::class);
    expect($traits)->toContain(Modules\Core\Locking\Traits\HasLocks::class);
    expect($traits)->toContain(Modules\Core\Search\Traits\Searchable::class);
});

it('content model has required methods', function (): void {
    $reflection = new ReflectionClass(Content::class);

    expect($reflection->hasMethod('contributors'))->toBeTrue();
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

it('content model has correct relationships', function (): void {
    $reflection = new ReflectionClass(Content::class);

    // Test contributors relationship
    $method = $reflection->getMethod('contributors');
    expect($method->getReturnType()->getName())->toBe(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);

    // Test categories relationship
    $method = $reflection->getMethod('categories');
    expect($method->getReturnType()->getName())->toBe(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);

    // Test locations relationship
    $method = $reflection->getMethod('locations');
    expect($method->getReturnType()->getName())->toBe(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);

    // Test related relationship
    $method = $reflection->getMethod('related');
    expect($method->getReturnType()->getName())->toBe(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
});

it('content model has correct method signatures', function (): void {
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

it('content model implements correct interfaces', function (): void {
    $reflection = new ReflectionClass(Content::class);

    expect($reflection->implementsInterface(Spatie\MediaLibrary\HasMedia::class))->toBeTrue();
    expect($reflection->implementsInterface(Spatie\EloquentSortable\Sortable::class))->toBeTrue();
});
