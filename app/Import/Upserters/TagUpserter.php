<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Upserters;

use Modules\CMS\Import\Dto\ImportTagDto;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportConnectionContext;
use Modules\CMS\Import\Support\ImportReferenceResolver;
use Modules\CMS\Models\Tag;
use Modules\Core\Import\Support\ImportFingerprint;

final class TagUpserter
{
    public function __construct(
        private readonly ExternalReferenceLocator $locator,
        private readonly ImportReferenceResolver $reference_resolver,
        private readonly string $locale,
    ) {}

    /**
     * @return list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public function participantModelClasses(): array
    {
        return [Tag::class, \Modules\CMS\Models\Translations\TagTranslation::class, \Modules\Core\Models\RecordOrigin::class];
    }

    public function upsert(ImportTagDto $dto, ?ImportConnectionContext $context = null): int
    {
        $context ??= new ImportConnectionContext(new Tag);
        $tag_model = $context->model(Tag::class);
        $existing_id = $this->reference_resolver->resolve(
            'tags',
            Tag::class,
            $dto->externalId,
            $dto->sourceType,
            $context,
        );

        if ($existing_id !== null) {
            // Bypass the soft-delete global scope: an id resolved from the origin
            // registry or a translation slug may point to a soft-deleted tag, which
            // must still be found (and reused) rather than crashing the import.
            $tag = $tag_model->newQueryWithoutScopes()->whereKey($existing_id)->firstOrFail();
        } else {
            $tag = $tag_model->newInstance(['type' => $dto->type]);
        }

        // A record that reappears in the source must be revived before it can be
        // updated: soft-deleted models reject updates ("Cannot update a softdeleted
        // model"). reviveInMemory() lets the save() below persist the restoration in
        // a single write. If the source still marks it deleted, it is re-deleted below.
        if ($tag->exists && $tag->trashed()) {
            $tag->reviveInMemory();
        }

        $tag->type = $dto->type;

        // The source owns the position when it has one; otherwise the tag keeps its own, and a new one goes last.
        if ($dto->orderColumn !== null) {
            $tag->order_column = $dto->orderColumn;
            $tag->keepPositionWhenCreating();
        }

        $tag->save();

        $tag->setTranslation($this->locale, [
            'name' => $dto->name,
            'slug' => $dto->slug,
        ]);

        if ($dto->deletedAt !== null && ! $tag->trashed()) {
            $tag->delete();
        }

        $tag_id = (int) $tag->id;
        $this->reference_resolver->remember('tags', $dto->externalId, $tag_id, $dto->sourceType, $context);

        $this->locator->register(
            $tag,
            $dto->sourceType,
            $dto->externalId,
            $dto->originLabel,
            $dto->originUrl,
            ImportFingerprint::of($dto),
            $dto->updatedAt,
        );

        return $tag_id;
    }
}
