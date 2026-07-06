<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Modules\CMS\Import\Dto\ImportCategoryDto;
use RuntimeException;

final class CategoryHierarchySorter
{
    /**
     * @param  list<ImportCategoryDto>  $categories
     * @return list<ImportCategoryDto>
     */
    public function sort(array $categories): array
    {
        if ($categories === []) {
            return [];
        }

        $by_external_id = [];

        foreach ($categories as $category) {
            $by_external_id[$category->externalId] = $category;
        }

        $sorted = [];
        $visited = [];

        foreach ($categories as $category) {
            $this->visit($category, $by_external_id, $visited, $sorted);
        }

        return $sorted;
    }

    /**
     * @param  array<int, ImportCategoryDto>  $byExternalId
     * @param  array<int, true>  $visited
     * @param  list<ImportCategoryDto>  $sorted
     */
    private function visit(
        ImportCategoryDto $category,
        array $byExternalId,
        array &$visited,
        array &$sorted,
    ): void {
        if (isset($visited[$category->externalId])) {
            return;
        }

        if ($category->parentExternalId !== null) {
            if (! isset($byExternalId[$category->parentExternalId])) {
                throw new RuntimeException(
                    "Missing parent category external id {$category->parentExternalId} for {$category->externalId}",
                );
            }

            $this->visit($byExternalId[$category->parentExternalId], $byExternalId, $visited, $sorted);
        }

        $visited[$category->externalId] = true;
        $sorted[] = $category;
    }
}
