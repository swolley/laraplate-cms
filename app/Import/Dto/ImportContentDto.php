<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Dto;

use Modules\CMS\Import\Support\ImportEntityNames;

/**
 * @phpstan-type ComponentsArray array<string, mixed>
 * @phpstan-type TranslationPayload array{title: string, slug: string, components: ComponentsArray}
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
     * @param  array<string, TranslationPayload>  $translations  Per-locale payloads merged onto the same content; base locale uses the top-level fields. Empty = single-locale behaviour.
     * @param  list<int>  $familyExternalIds  All external ids belonging to the same content family (translations), mapped to the same local id for idempotency.
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
        public string $entityName = ImportEntityNames::CONTENTS,
        public string $presetName = 'default',
        public string $sourceKind = 'story',
        public string $categorySourceKind = 'section',
        public ?string $originLabel = null,
        public ?string $originUrl = null,
        public array $translations = [],
        public array $familyExternalIds = [],
    ) {}

    /**
     * @param  list<int>  $contributorExternalIds
     */
    public function withContributorExternalIds(array $contributorExternalIds): self
    {
        return $this->copyWith(contributorExternalIds: $contributorExternalIds);
    }

    public function withOrigin(?string $originLabel, ?string $originUrl): self
    {
        return $this->copyWith(originLabel: $originLabel, originUrl: $originUrl);
    }

    /**
     * @param  list<ImportRelatedContentDto>  $relatedContents
     */
    public function withRelatedContents(array $relatedContents): self
    {
        return $this->copyWith(relatedContents: $relatedContents);
    }

    /**
     * @param  array<string, TranslationPayload>  $translations
     * @param  list<int>  $familyExternalIds
     */
    public function withTranslations(array $translations, array $familyExternalIds = []): self
    {
        return $this->copyWith(translations: $translations, familyExternalIds: $familyExternalIds);
    }

    /**
     * Immutable copy overriding only the provided fields.
     *
     * @param  list<int>|null  $categoryExternalIds
     * @param  list<int>|null  $contributorExternalIds
     * @param  list<int>|null  $tagExternalIds
     * @param  list<ImportRelatedContentDto>|null  $relatedContents
     * @param  array<string, TranslationPayload>|null  $translations
     * @param  list<int>|null  $familyExternalIds
     */
    private function copyWith(
        ?array $categoryExternalIds = null,
        ?array $contributorExternalIds = null,
        ?array $tagExternalIds = null,
        ?array $relatedContents = null,
        ?string $originLabel = null,
        ?string $originUrl = null,
        ?array $translations = null,
        ?array $familyExternalIds = null,
    ): self {
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
            categoryExternalIds: $categoryExternalIds ?? $this->categoryExternalIds,
            contributorExternalIds: $contributorExternalIds ?? $this->contributorExternalIds,
            tagExternalIds: $tagExternalIds ?? $this->tagExternalIds,
            relatedContents: $relatedContents ?? $this->relatedContents,
            entityName: $this->entityName,
            presetName: $this->presetName,
            sourceKind: $this->sourceKind,
            categorySourceKind: $this->categorySourceKind,
            originLabel: $originLabel ?? $this->originLabel,
            originUrl: $originUrl ?? $this->originUrl,
            translations: $translations ?? $this->translations,
            familyExternalIds: $familyExternalIds ?? $this->familyExternalIds,
        );
    }
}
