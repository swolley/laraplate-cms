<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Dto;

/**
 * @phpstan-type ComponentsArray array<string, mixed>
 */
final readonly class ImportContributorDto
{
    /**
     * @param  ComponentsArray  $components
     * @param  ComponentsArray  $sharedComponents
     */
    public function __construct(
        public int $externalId,
        public string $name,
        public ?string $slug,
        public array $components,
        public array $sharedComponents,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
        public string $sourceType,
        public string $entityName = 'contributor',
        public string $presetName = 'default',
    ) {}
}
