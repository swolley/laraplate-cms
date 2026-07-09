<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Modules\CMS\Import\Dto\ImportCategoryDto;
use Modules\CMS\Models\Category;

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

        $parent_external_id = $category->parentExternalId;

        if ($parent_external_id !== null) {
            if (isset($byExternalId[$parent_external_id])) {
                $this->visit($byExternalId[$parent_external_id], $byExternalId, $visited, $sorted);
            }
        }

        $visited[$category->externalId] = true;
        $sorted[] = $category;
    }
}
