<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Modules\CMS\Import\Dto\ImportRelatedContentDto;

final class RelatedContentResolver
{
    public function __construct(
        private readonly ExternalReferenceLocator $locator,
        private readonly ImportIdMap $id_map,
    ) {}

    /**
     * @param  list<ImportRelatedContentDto>  $relatedContents
     * @return list<int>
     */
    public function resolveContentIds(array $relatedContents): array
    {
        $resolved = [];

        foreach ($relatedContents as $related) {
            $content_id = $this->id_map->resolve('contents', $related->externalId)
                ?? $this->locator->findContentId($related->externalId, $related->sourceType);

            if ($content_id !== null) {
                $resolved[] = $content_id;
            }
        }

        return array_values(array_unique($resolved));
    }
}
