<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Dto;

final readonly class ImportRelatedContentDto
{
    public function __construct(
        public int $externalId,
        public string $sourceKind,
        public string $sourceType,
    ) {}
}
