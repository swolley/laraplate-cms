<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Upserters;

use Modules\CMS\Import\Dto\ImportContentDto;
use Modules\CMS\Import\Support\EntityPresetResolver;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportIdMap;
use Modules\CMS\Import\Support\RelatedContentResolver;
use Modules\CMS\Models\Content;

final class ContentUpserter
{
    public function __construct(
        private readonly EntityPresetResolver $entity_preset_resolver,
        private readonly ExternalReferenceLocator $locator,
        private readonly ImportIdMap $id_map,
        private readonly RelatedContentResolver $related_content_resolver,
        private readonly string $locale,
    ) {}

    /**
     * @param  list<int>  $categoryIds
     * @param  list<int>  $contributorIds
     * @param  list<int>  $tagIds
     * @param  list<int>  $locationIds
     */
    public function upsert(
        ImportContentDto $dto,
        array $categoryIds = [],
        array $contributorIds = [],
        array $tagIds = [],
        array $locationIds = [],
    ): int {
        $existing_id = $this->id_map->resolve('contents', $dto->externalId)
            ?? $this->locator->findContentId($dto->externalId, $dto->sourceType)
            ?? $this->locator->findContentIdBySlug($dto->slug);

        $entity_id = $this->entity_preset_resolver->entityId($dto->entityName);
        $presettable_id = $this->entity_preset_resolver->presettableId($dto->entityName, $dto->presetName);

        if ($existing_id !== null) {
            $content = Content::query()->withoutGlobalScopes()->findOrFail($existing_id);
        } else {
            $content = new Content([
                'entity_id' => $entity_id,
                'presettable_id' => $presettable_id,
            ]);
        }

        $content->entity_id = $entity_id;
        $content->presettable_id = $presettable_id;
        $content->shared_components = $dto->sharedComponents;
        $content->origin_label = $dto->originLabel;
        $content->origin_url = $dto->originUrl;
        $content->valid_from = $dto->validFrom;
        $content->valid_to = $dto->validTo;
        $content->save();

        $content->setTranslation($this->locale, [
            'title' => $dto->title,
            'slug' => $dto->slug,
            'components' => $dto->components,
        ]);
        $content->save();

        if ($categoryIds !== []) {
            $content->categories()->sync($categoryIds);
        }

        if ($contributorIds !== []) {
            $content->contributors()->sync($contributorIds);
        }

        if ($tagIds !== []) {
            $content->tags()->sync($tagIds);
        }

        if ($locationIds !== []) {
            $content->locations()->sync($locationIds);
        }

        $related_ids = $this->related_content_resolver->resolveContentIds($dto->relatedContents);

        if ($related_ids !== []) {
            $content->related()->sync($related_ids);
        }

        if ($dto->deletedAt !== null && ! $content->trashed()) {
            $content->delete();
        }

        $content_id = (int) $content->id;
        $this->id_map->remember('contents', $dto->externalId, $content_id);

        return $content_id;
    }
}
