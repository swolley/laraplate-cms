<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Dto;

use Modules\CMS\Import\Support\ImportEntityNames;

/**
 * @phpstan-type ComponentsArray array<string, mixed>
 */
final readonly class ImportCategoryDto
{
    /**
     * @param  ComponentsArray  $components
     * @param  ComponentsArray  $sharedComponents
     */
    public function __construct(
        public int $externalId,
        public string $name,
        public string $slug,
        public ?int $parentExternalId,
        public array $components,
        public array $sharedComponents,
        public bool $isActive,
        public int $orderColumn,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
        public string $sourceType,
        public string $sourceKind = 'section',
        public string $entityName = ImportEntityNames::CATEGORIES,
        public string $presetName = 'default',
    ) {}
}
