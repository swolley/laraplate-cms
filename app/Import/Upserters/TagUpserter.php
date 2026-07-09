<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Upserters;

use Modules\CMS\Import\Dto\ImportTagDto;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportReferenceResolver;
use Modules\CMS\Models\Tag;

final class TagUpserter
{
    public function __construct(
        private readonly ExternalReferenceLocator $locator,
        private readonly ImportReferenceResolver $reference_resolver,
        private readonly string $locale,
    ) {}

    public function upsert(ImportTagDto $dto): int
    {
        $existing_id = $this->reference_resolver->resolve(
            'tags',
            Tag::class,
            $dto->externalId,
            $dto->sourceType,
        );

        if ($existing_id !== null) {
            // Bypass the soft-delete global scope: an id resolved from the origin
            // registry or a translation slug may point to a soft-deleted tag, which
            // must still be found (and reused) rather than crashing the import.
            $tag = Tag::query()->withoutGlobalScopes()->findOrFail($existing_id);
        } else {
            $tag = new Tag([
                'type' => $dto->type,
                'order_column' => $dto->orderColumn,
            ]);
        }

        // A record that reappears in the source must be revived before it can be
        // updated: soft-deleted models reject updates ("Cannot update a softdeleted
        // model"). reviveInMemory() lets the save() below persist the restoration in
        // a single write. If the source still marks it deleted, it is re-deleted below.
        if ($tag->exists && $tag->trashed()) {
            $tag->reviveInMemory();
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
        $this->reference_resolver->remember('tags', $dto->externalId, $tag_id);

        $this->locator->register($tag, $dto->sourceType, $dto->externalId);

        return $tag_id;
    }
}
