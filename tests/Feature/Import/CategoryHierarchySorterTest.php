<?php

declare(strict_types=1);

use Modules\CMS\Import\Dto\ImportCategoryDto;
use Modules\CMS\Import\Support\CategoryHierarchySorter;
use Modules\CMS\Import\Support\ImportEntityNames;

it('orders parent categories before children when both are in the graph', function (): void {
    $parent = new ImportCategoryDto(
        externalId: 9,
        name: 'Parent',
        slug: 'parent',
        parentExternalId: null,
        components: [],
        sharedComponents: [],
        isActive: true,
        orderColumn: 0,
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        sourceType: 'naxos_api@test',
        entityName: ImportEntityNames::CATEGORIES,
        presetName: 'default',
    );

    $child = new ImportCategoryDto(
        externalId: 33,
        name: 'Child',
        slug: 'child',
        parentExternalId: 9,
        components: [],
        sharedComponents: [],
        isActive: true,
        orderColumn: 0,
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        sourceType: 'naxos_api@test',
        entityName: ImportEntityNames::CATEGORIES,
        presetName: 'default',
    );

    $sorted = resolve(CategoryHierarchySorter::class)->sort([$child, $parent]);

    expect(array_map(static fn (ImportCategoryDto $category): int => $category->externalId, $sorted))
        ->toBe([9, 33]);
});

it('does not fail when a parent category is missing from the current graph', function (): void {
    $child = new ImportCategoryDto(
        externalId: 33,
        name: 'Child',
        slug: 'child',
        parentExternalId: 9,
        components: [],
        sharedComponents: [],
        isActive: true,
        orderColumn: 0,
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        sourceType: 'naxos_api@test',
        entityName: ImportEntityNames::CATEGORIES,
        presetName: 'default',
    );

    $sorted = resolve(CategoryHierarchySorter::class)->sort([$child]);

    expect($sorted)->toHaveCount(1)
        ->and($sorted[0]->externalId)->toBe(33);
});
