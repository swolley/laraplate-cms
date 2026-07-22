<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Modules\CMS\Import\Dto\ImportRelatedContentDto;
use Modules\CMS\Models\Content;

final class RelatedContentResolver
{
    public function __construct(
        private readonly ImportReferenceResolver $reference_resolver,
    ) {}

    /**
     * @param  list<ImportRelatedContentDto>  $relatedContents
     * @return list<int>
     */
    public function resolveContentIds(array $relatedContents, ?ImportConnectionContext $context = null): array
    {
        $resolved = [];

        foreach ($relatedContents as $related) {
            $content_id = $this->reference_resolver->resolve(
                'contents',
                Content::class,
                $related->externalId,
                $related->sourceType,
                $context,
            );

            if ($content_id !== null) {
                $resolved[] = $content_id;
            }
        }

        return array_values(array_unique($resolved));
    }
}
