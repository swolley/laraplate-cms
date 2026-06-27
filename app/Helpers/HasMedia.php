<?php

declare(strict_types=1);

namespace Modules\CMS\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Events\CollectionHasBeenClearedEvent;
use Spatie\MediaLibrary\MediaCollections\Exceptions\MediaCannotBeDeleted;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait HasMedia
{
    use InteractsWithMedia;

    /**
     * @return Builder<Media>
     */
    public function trashedMedia(): Builder
    {
        /** @var Builder<Media> $query */
        $query = $this->media()->getQuery();

        return $query->onlyTrashed();
    }

    /**
     * @return Builder<Media>
     */
    public function allMedia(): Builder
    {
        /** @var Builder<Media> $query */
        $query = $this->media()->withoutGlobalScope(SoftDeletingScope::class)->getQuery();

        return $query;
    }

    public function forceClearMediaCollection(string $collectionName = 'default'): static
    {
        $this
            ->getMedia($collectionName)
            ->each(static fn (Media $media) => $media->forceDelete());

        event(new CollectionHasBeenClearedEvent($this, $collectionName));

        if ($this->mediaIsPreloaded()) {
            unset($this->media);
        }

        return $this;
    }

    /**
     * @param  array<int, Media>|Collection<int, Media>|Media  $excludedMedia
     */
    public function forceClearMediaCollectionExcept(
        string $collectionName = 'default',
        array|Collection|Media $excludedMedia = [],
    ): static {
        if ($excludedMedia instanceof Media) {
            $excludedMedia = collect()->push($excludedMedia);
        }

        $excluded_media = collect($excludedMedia);

        if ($excluded_media->isEmpty()) {
            return $this->forceClearMediaCollection($collectionName);
        }

        $this
            ->getMedia($collectionName)
            ->reject(fn (Media $media): bool => $excluded_media->contains(
                static fn (Media $excluded): bool => $excluded->getKey() === $media->getKey(),
            ))
            ->each(static fn (Media $media) => $media->forceDelete());

        if ($this->mediaIsPreloaded()) {
            unset($this->media);
        }

        if ($this->getMedia($collectionName)->isEmpty()) {
            event(new CollectionHasBeenClearedEvent($this, $collectionName));
        }

        return $this;
    }

    /**
     * Delete the associated media with the given id.
     * You may also pass a media object.
     *
     * @throws MediaCannotBeDeleted
     */
    public function forceDeleteMedia(int|string|Media $mediaId): void
    {
        if ($mediaId instanceof Media) {
            $mediaId = $mediaId->getKey();
        }

        $media = $this->allMedia()->whereKey($mediaId)->first();

        throw_unless($media instanceof Media, MediaCannotBeDeleted::doesNotBelongToModel($mediaId, $this));

        $media->forceDelete();
    }

    public function purgePreservingMedia(): bool
    {
        $this->deletePreservingMedia = true;

        return (bool) $this->forceDelete();
    }

    public function restoreMedia(int|string|Media $mediaId): void
    {
        $key = $mediaId instanceof Media ? $mediaId->getKey() : $mediaId;

        $this->trashedMedia()->whereKey($key)->get()->each(static fn (Media $media) => $media->restore());
    }

    public function restoreAllMedia(string $collectionName = 'default'): void
    {
        $this->trashedMedia()
            ->where('collection_name', $collectionName)
            ->get()
            ->each(static fn (Media $media) => $media->restore());
    }

    public function forceDeleteAllMedia(string $collectionName = 'default'): void
    {
        $this->allMedia()
            ->where('collection_name', $collectionName)
            ->get()
            ->each(static fn (Media $media) => $media->forceDelete());
    }

    /**
     * @param  list<array<string, mixed>>  $newMediaArray
     */
    protected function removeMediaItemsNotPresentInArray(array $newMediaArray, string $collectionName = 'default'): void
    {
        $this
            ->getMedia($collectionName)
            ->reject(fn (Media $current_media_item): bool => in_array(
                $current_media_item->getKey(),
                array_column($newMediaArray, $current_media_item->getKeyName()),
                true,
            ))
            ->each(static fn (Media $media) => $media->delete());

        if ($this->mediaIsPreloaded()) {
            unset($this->media);
        }
    }
}
