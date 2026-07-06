<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Upserters;

use Modules\CMS\Import\Dto\ImportTagDto;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportIdMap;
use Modules\CMS\Models\Tag;

final class TagUpserter
{
    public function __construct(
        private readonly ExternalReferenceLocator $locator,
        private readonly ImportIdMap $id_map,
        private readonly string $locale,
    ) {}

    public function upsert(ImportTagDto $dto): int
    {
        $existing_id = $this->id_map->resolve('tags', $dto->externalId)
            ?? $this->locator->findTagIdBySlug($dto->slug)
            ?? $this->locator->findTagId($dto->externalId, $dto->sourceType);

        if ($existing_id !== null) {
            $tag = Tag::query()->findOrFail($existing_id);
        } else {
            $tag = new Tag([
                'type' => $dto->type,
                'order_column' => $dto->orderColumn,
            ]);
        }

        $tag->type = $dto->type;
        $tag->order_column = $dto->orderColumn;
        $tag->save();

        $tag->setTranslation($this->locale, [
            'name' => $dto->name,
            'slug' => $dto->slug,
        ]);

        if ($dto->deletedAt !== null && ! $tag->trashed()) {
            $tag->delete();
        }

        $tag_id = (int) $tag->id;
        $this->id_map->remember('tags', $dto->externalId, $tag_id);

        return $tag_id;
    }
}
