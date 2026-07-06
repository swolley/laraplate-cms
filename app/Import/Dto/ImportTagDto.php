<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Dto;

final readonly class ImportTagDto
{
    public function __construct(
        public int $externalId,
        public string $name,
        public string $slug,
        public ?string $type,
        public int $orderColumn,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
        public string $sourceType,
    ) {}
}
