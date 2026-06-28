<?php

declare(strict_types=1);

namespace Modules\CMS\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Modules\CMS\Models\Media as CmsMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Events\CollectionHasBeenClearedEvent;
use Spatie\MediaLibrary\MediaCollections\Exceptions\MediaCannotBeDeleted;

/**
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait HasMedia
{
    use InteractsWithMedia;

    /**
     * @return Builder<CmsMedia>
     */
    public function trashedMedia(): Builder
    {
        return $this->mediaRelationQuery()->onlyTrashed();
    }

    /**
     * @return Builder<CmsMedia>
     */
    public function allMedia(): Builder
    {
        return $this->mediaRelationQuery()->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function forceClearMediaCollection(string $collectionName = 'default'): static
    {
        $this
            ->getMedia($collectionName)
            ->each(static function (mixed $media): void {
                if ($media instanceof CmsMedia) {
                    $media->forceDelete();
                }
            });

        event(new CollectionHasBeenClearedEvent($this, $collectionName));

        if ($this->mediaIsPreloaded()) {
            unset($this->media);
        }

        return $this;
    }

    /**
     * @param  array<int, CmsMedia>|Collection<int, CmsMedia>|CmsMedia  $excludedMedia
     */
    public function forceClearMediaCollectionExcept(
        string $collectionName = 'default',
        array|Collection|CmsMedia $excludedMedia = [],
    ): static {
        if ($excludedMedia instanceof CmsMedia) {
            $excludedMedia = collect()->push($excludedMedia);
        }

        $excluded_media = collect($excludedMedia);

        if ($excluded_media->isEmpty()) {
            return $this->forceClearMediaCollection($collectionName);
        }

        $this
            ->getMedia($collectionName)
            ->reject(fn (mixed $media): bool => $media instanceof CmsMedia && $excluded_media->contains(
                static fn (mixed $excluded): bool => $excluded instanceof CmsMedia && $excluded->getKey() === $media->getKey(),
            ))
            ->each(static function (mixed $media): void {
                if ($media instanceof CmsMedia) {
                    $media->forceDelete();
                }
            });

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
    public function forceDeleteMedia(int|string|CmsMedia $mediaId): void
    {
        if ($mediaId instanceof CmsMedia) {
            $mediaId = $mediaId->getKey();
        }

        $media = $this->allMedia()->whereKey($mediaId)->first();

        throw_unless($media instanceof CmsMedia, MediaCannotBeDeleted::doesNotBelongToModel($mediaId, $this));

        $media->forceDelete();
    }

    public function purgePreservingMedia(): bool
    {
        $this->deletePreservingMedia = true;

        return (bool) $this->forceDelete();
    }

    public function restoreMedia(int|string|CmsMedia $mediaId): void
    {
        $key = $mediaId instanceof CmsMedia ? $mediaId->getKey() : $mediaId;

        $this->trashedMedia()->whereKey($key)->get()->each(static fn (CmsMedia $media) => $media->restore());
    }

    public function restoreAllMedia(string $collectionName = 'default'): void
    {
        $this->trashedMedia()
            ->where('collection_name', $collectionName)
            ->get()
            ->each(static fn (CmsMedia $media) => $media->restore());
    }

    public function forceDeleteAllMedia(string $collectionName = 'default'): void
    {
        $this->allMedia()
            ->where('collection_name', $collectionName)
            ->get()
            ->each(static fn (CmsMedia $media) => $media->forceDelete());
    }

    /**
     * @param  list<array<string, mixed>>  $newMediaArray
     */
    protected function removeMediaItemsNotPresentInArray(array $newMediaArray, string $collectionName = 'default'): void
    {
        $this
            ->getMedia($collectionName)
            ->reject(fn (mixed $current_media_item): bool => $current_media_item instanceof CmsMedia && in_array(
                $current_media_item->getKey(),
                array_column($newMediaArray, $current_media_item->getKeyName()),
                true,
            ))
            ->each(static function (mixed $media): void {
                if ($media instanceof CmsMedia) {
                    $media->delete();
                }
            });

        if ($this->mediaIsPreloaded()) {
            unset($this->media);
        }
    }

    /**
     * @return Builder<CmsMedia>
     */
    private function mediaRelationQuery(): Builder
    {
        return CmsMedia::query()
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', $this->getKey());
    }
}
