<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Dto;

/**
 * @phpstan-type ComponentsArray array<string, mixed>
 */
final readonly class ImportContentDto
{
    /**
     * @param  ComponentsArray  $components
     * @param  ComponentsArray  $sharedComponents
     * @param  list<int>  $categoryExternalIds
     * @param  list<int>  $contributorExternalIds
     * @param  list<int>  $tagExternalIds
     * @param  list<ImportRelatedContentDto>  $relatedContents
     */
    public function __construct(
        public string $title,
        public string $slug,
        public array $components,
        public array $sharedComponents,
        public ?string $validFrom,
        public ?string $validTo,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
        public int $externalId,
        public ?string $externalUuid,
        public string $sourceType,
        public array $categoryExternalIds = [],
        public array $contributorExternalIds = [],
        public array $tagExternalIds = [],
        public array $relatedContents = [],
        public string $entityName = 'post',
        public string $presetName = 'standard',
        public string $sourceKind = 'story',
        public string $categorySourceKind = 'section',
        public ?string $originLabel = null,
        public ?string $originUrl = null,
    ) {}

    /**
     * @param  list<int>  $contributorExternalIds
     */
    public function withContributorExternalIds(array $contributorExternalIds): self
    {
        return new self(
            title: $this->title,
            slug: $this->slug,
            components: $this->components,
            sharedComponents: $this->sharedComponents,
            validFrom: $this->validFrom,
            validTo: $this->validTo,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            deletedAt: $this->deletedAt,
            externalId: $this->externalId,
            externalUuid: $this->externalUuid,
            sourceType: $this->sourceType,
            categoryExternalIds: $this->categoryExternalIds,
            contributorExternalIds: $contributorExternalIds,
            tagExternalIds: $this->tagExternalIds,
            relatedContents: $this->relatedContents,
            entityName: $this->entityName,
            presetName: $this->presetName,
            sourceKind: $this->sourceKind,
            categorySourceKind: $this->categorySourceKind,
            originLabel: $this->originLabel,
            originUrl: $this->originUrl,
        );
    }

    public function withOrigin(?string $originLabel, ?string $originUrl): self
    {
        return new self(
            title: $this->title,
            slug: $this->slug,
            components: $this->components,
            sharedComponents: $this->sharedComponents,
            validFrom: $this->validFrom,
            validTo: $this->validTo,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            deletedAt: $this->deletedAt,
            externalId: $this->externalId,
            externalUuid: $this->externalUuid,
            sourceType: $this->sourceType,
            categoryExternalIds: $this->categoryExternalIds,
            contributorExternalIds: $this->contributorExternalIds,
            tagExternalIds: $this->tagExternalIds,
            relatedContents: $this->relatedContents,
            entityName: $this->entityName,
            presetName: $this->presetName,
            sourceKind: $this->sourceKind,
            categorySourceKind: $this->categorySourceKind,
            originLabel: $originLabel,
            originUrl: $originUrl,
        );
    }

    /**
     * @param  list<ImportRelatedContentDto>  $relatedContents
     */
    public function withRelatedContents(array $relatedContents): self
    {
        return new self(
            title: $this->title,
            slug: $this->slug,
            components: $this->components,
            sharedComponents: $this->sharedComponents,
            validFrom: $this->validFrom,
            validTo: $this->validTo,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            deletedAt: $this->deletedAt,
            externalId: $this->externalId,
            externalUuid: $this->externalUuid,
            sourceType: $this->sourceType,
            categoryExternalIds: $this->categoryExternalIds,
            contributorExternalIds: $this->contributorExternalIds,
            tagExternalIds: $this->tagExternalIds,
            relatedContents: $relatedContents,
            entityName: $this->entityName,
            presetName: $this->presetName,
            sourceKind: $this->sourceKind,
            categorySourceKind: $this->categorySourceKind,
            originLabel: $this->originLabel,
            originUrl: $this->originUrl,
        );
    }
}
