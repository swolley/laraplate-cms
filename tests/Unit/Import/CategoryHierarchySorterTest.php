<?php

declare(strict_types=1);

use Modules\CMS\Import\Dto\ImportCategoryDto;
use Modules\CMS\Import\Support\CategoryHierarchySorter;

it('sorts parent categories before children', function (): void {
    $sorter = new CategoryHierarchySorter();

    $parent = new ImportCategoryDto(
        externalId: 1,
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
        sourceType: 'fixture',
    );

    $child = new ImportCategoryDto(
        externalId: 2,
        name: 'Child',
        slug: 'child',
        parentExternalId: 1,
        components: [],
        sharedComponents: [],
        isActive: true,
        orderColumn: 0,
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        sourceType: 'fixture',
    );

    $sorted = $sorter->sort([$child, $parent]);

    expect($sorted[0]->externalId)->toBe(1)
        ->and($sorted[1]->externalId)->toBe(2);
});
