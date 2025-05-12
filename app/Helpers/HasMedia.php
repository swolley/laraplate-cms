<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Events\CollectionHasBeenClearedEvent;
use Spatie\MediaLibrary\MediaCollections\Exceptions\MediaCannotBeDeleted;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasMedia
{
    use InteractsWithMedia;

    public function trashedMedia(): MorphMany
    {
        return $this->media()->onlyTrashed();
    }

    public function allMedia(): MorphMany
    {
        return $this->media()->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function forceClearMediaCollection(string $collectionName = 'default'): static
    {
        $this
            ->getMedia($collectionName)
            ->each(fn (Media $media) => $media->forceDelete());

        event(new CollectionHasBeenClearedEvent($this, $collectionName));

        if ($this->mediaIsPreloaded()) {
            unset($this->media);
        }

        return $this;
    }

    public function forceClearMediaCollectionExcept(
        string $collectionName = 'default',
        array|Collection|Media $excludedMedia = [],
    ): static {
        if ($excludedMedia instanceof Media) {
            $excludedMedia = collect()->push($excludedMedia);
        }

        $excludedMedia = collect($excludedMedia);

        if ($excludedMedia->isEmpty()) {
            return $this->forceClearMediaCollection($collectionName);
        }

        $this
            ->getMedia($collectionName)
            ->reject(fn (Media $media) => $excludedMedia->where($media->getKeyName(), $media->getKey())->count())
            ->each(fn (Media $media) => $media->forceDelete());

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

        $media = $this->allMedia->find($mediaId);

        if (! $media) {
            throw MediaCannotBeDeleted::doesNotBelongToModel($mediaId, $this);
        }

        $media->forceDelete();
    }

    public function purgePreservingMedia(): bool
    {
        $this->deletePreservingMedia = true;

        return $this->forceDelete();
    }

    public function restoreMedia(int|string|Media $mediaId): void
    {
        $this->trashedMedia()->where('id', $mediaId instanceof Media ? $mediaId->getKey() : $mediaId)->restore();
    }

    public function restoreAllMedia(string $collectionName = 'default'): void
    {
        $this->trashedMedia()
            ->where('collection_name', $collectionName)
            ->restore();
    }

    public function forceDeleteAllMedia(string $collectionName = 'default'): void
    {
        $this->allMedia()
            ->where('collection_name', $collectionName)
            ->forceDelete();
    }

    protected function removeMediaItemsNotPresentInArray(array $newMediaArray, string $collectionName = 'default'): void
    {
        $this
            ->getMedia($collectionName)
            ->reject(fn (Media $currentMediaItem): bool => in_array(
                $currentMediaItem->getKey(),
                array_column($newMediaArray, $currentMediaItem->getKeyName()),
                true,
            ))
            ->each(fn (Media $media) => $media->delete());

        if ($this->mediaIsPreloaded()) {
            unset($this->media);
        }
    }
}
