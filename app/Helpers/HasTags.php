<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use ArrayAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Modules\Cms\Models\Tag;

trait HasTags
{
    protected array $queuedTags = [];

    public static function bootHasTags(): void
    {
        static::created(function (Model $taggableModel): void {
            if (count($taggableModel->queuedTags) === 0) {
                return;
            }

            $taggableModel->attachTags($taggableModel->queuedTags);

            $taggableModel->queuedTags = [];
        });

        static::deleted(function (Model $deletedModel): void {
            $tags = $deletedModel->tags()->get();

            $deletedModel->detachTags($tags);
        });
    }

    public static function scopeWithAllTags(
        Builder $query,
        string|array|ArrayAccess|Tag $tags,
        ?string $type = null,
    ): Builder {
        $tags = static::convertToTags($tags, $type);

        collect($tags)->each(function ($tag) use ($query): void {
            $query->whereHas('tags', function (Builder $query) use ($tag): void {
                $query->where('tags.id', $tag->id ?? 0);
            });
        });

        return $query;
    }

    public static function scopeWithAnyTags(
        Builder $query,
        string|array|ArrayAccess|Tag $tags,
        ?string $type = null,
    ): Builder {
        $tags = static::convertToTags($tags, $type);

        return $query
            ->whereHas('tags', function (Builder $query) use ($tags): void {
                $tagIds = collect($tags)->pluck('id');

                $query->whereIn('tags.id', $tagIds);
            });
    }

    public static function scopeWithoutTags(
        Builder $query,
        string|array|ArrayAccess|Tag $tags,
        ?string $type = null,
    ): Builder {
        $tags = static::convertToTags($tags, $type);

        return $query
            ->whereDoesntHave('tags', function (Builder $query) use ($tags): void {
                $tagIds = collect($tags)->pluck('id');

                $query->whereIn('tags.id', $tagIds);
            });
    }

    public static function scopeWithAllTagsOfAnyType(Builder $query, $tags): Builder
    {
        $tags = static::convertToTagsOfAnyType($tags);

        collect($tags)
            ->each(function ($tag) use ($query): void {
                $query->whereHas(
                    'tags',
                    fn (Builder $query) => $query->where('tags.id', $tag ? $tag->id : 0),
                );
            });

        return $query;
    }

    public static function scopeWithAnyTagsOfAnyType(Builder $query, $tags): Builder
    {
        $tags = static::convertToTagsOfAnyType($tags);

        $tagIds = collect($tags)->pluck('id');

        return $query->whereHas(
            'tags',
            fn (Builder $query) => $query->whereIn('tags.id', $tagIds),
        );
    }

    public function tags(): MorphToMany
    {
        return $this
            ->morphToMany(Tag::class, 'taggable', 'taggables')
            ->using(MorphPivot::class)
            ->ordered();
    }

    public function tagsWithType(?string $type = null): Collection
    {
        return $this->tags->filter(fn (Tag $tag): bool => $tag->type === $type);
    }

    public function attachTags(array|ArrayAccess|Tag $tags, ?string $type = null): static
    {
        $tags = collect(Tag::findOrCreate($tags, $type));

        $this->tags()->syncWithoutDetaching($tags->pluck('id')->toArray());

        return $this;
    }

    public function attachTag(string|Tag $tag, ?string $type = null)
    {
        return $this->attachTags([$tag], $type);
    }

    public function detachTags(array|ArrayAccess $tags, ?string $type = null): static
    {
        $tags = static::convertToTags($tags, $type);

        collect($tags)
            ->filter()
            ->each(fn (Tag $tag) => $this->tags()->detach($tag));

        return $this;
    }

    public function detachTag(string|Tag $tag, ?string $type = null): static
    {
        return $this->detachTags([$tag], $type);
    }

    public function syncTags(string|array|ArrayAccess $tags): static
    {
        if (is_string($tags)) {
            $tags = Arr::wrap($tags);
        }

        $tags = collect(Tag::findOrCreate($tags));

        $this->tags()->sync($tags->pluck('id')->toArray());

        return $this;
    }

    public function syncTagsWithType(array|ArrayAccess $tags, ?string $type = null): static
    {
        $tags = collect(Tag::findOrCreate($tags, $type));

        $this->syncTagIds($tags->pluck('id')->toArray(), $type);

        return $this;
    }

    public function hasTag($tag, ?string $type = null): bool
    {
        return $this->tags
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->contains(fn ($modelTag): bool => $modelTag->name === $tag || $modelTag->id === $tag);
    }

    protected static function convertToTags($values, $type = null)
    {
        if ($values instanceof Tag) {
            $values = [$values];
        }

        return collect($values)->map(function ($value) use ($type) {
            if ($value instanceof Tag) {
                throw_if(isset($type) && $value->type !== $type, InvalidArgumentException::class, "Type was set to {$type} but tag is of type {$value->type}");

                return $value;
            }

            return Tag::findFromString($value, $type);
        });
    }

    protected static function convertToTagsOfAnyType($values)
    {
        return collect($values)->map(function ($value): Tag|\Illuminate\Support\Collection {
            if ($value instanceof Tag) {
                return $value;
            }

            return Tag::findFromStringOfAnyType($value);
        })->flatten();
    }

    protected function setTagsAttribute(string|array|ArrayAccess|Tag $tags): void
    {
        if (! $this->exists) {
            $this->queuedTags = $tags;

            return;
        }

        $this->syncTags($tags);
    }

    protected function syncTagIds($ids, ?string $type = null, $detaching = true): void
    {
        $isUpdated = false;

        $tagModel = $this->tags()->getRelated();

        // Get a list of tag_ids for all current tags
        $current = $this->tags()
            ->newPivotStatement()
            ->where('taggable_id', $this->getKey())
            ->where('taggable_type', $this->getMorphClass())
            ->join(
                $tagModel->getTable(),
                'taggables.tag_id',
                '=',
                $tagModel->getTable() . '.' . $tagModel->getKeyName(),
            )
            ->where($tagModel->getTable() . '.type', $type)
            ->pluck('tag_id')
            ->all();

        // Compare to the list of ids given to find the tags to remove
        $detach = array_diff($current, $ids);

        if ($detaching && $detach !== []) {
            $this->tags()->detach($detach);
            $isUpdated = true;
        }

        // Attach any new ids
        $attach = array_unique(array_diff($ids, $current));

        if ($attach !== []) {
            $this->tags()->attach($attach, []);

            $isUpdated = true;
        }

        // Once we have finished attaching or detaching the records, we will see if we
        // have done any attaching or detaching, and if we have we will touch these
        // relationships if they are configured to touch on any database updates.
        if ($isUpdated) {
            $this->tags()->touchIfTouching();
        }
    }
}
