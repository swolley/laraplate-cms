<?php

declare(strict_types=1);

use Modules\Cms\Models\Category;
use Modules\Cms\Tests\TestCase;

uses(TestCase::class);

it('appends cover after initializeHasMultiMedia', function (): void {
    $category = new Category;
    $category->initializeHasMultiMedia();

    expect($category->getAppends())->toContain('cover');
});

it('does not duplicate cover in appends when called twice', function (): void {
    $category = new Category;
    $category->mergeAppends(['cover']);
    $category->initializeHasMultiMedia();

    expect(array_count_values($category->getAppends())['cover'] ?? 0)->toBe(1);
});

it('registers expected media collections', function (): void {
    $category = new Category;
    $category->registerMediaCollections();

    $names = array_map(
        static fn ($collection) => $collection->name,
        $category->mediaCollections,
    );

    expect($names)->toContain('cover', 'images', 'videos', 'audios', 'files');
});

it('registers image and video conversions without error', function (): void {
    $category = new Category;
    $category->registerMediaConversions(null);

    expect($category->mediaConversions)->not->toBeEmpty();
});
