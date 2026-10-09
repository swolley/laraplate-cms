<?php

declare(strict_types=1);

namespace Modules\CMS\Helpers;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Core\Contracts\IPreloadsAppendedRelations;
use Modules\Core\Helpers\HasMedia;
use Modules\Core\Models\Media as CmsMedia;
use Modules\Core\Services\Crud\RelationAuthorizer;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait HasMultimedia
{
    use HasMedia;

    public function initializeHasMultimedia(): void
    {
        if (! in_array('cover', $this->appends, true)) {
            $this->appends[] = 'cover';
        }

        if (! in_array('coverMedia', $this->hidden, true)) {
            $this->hidden[] = 'coverMedia';
        }
    }

    /**
     * The relation the `cover` attribute reads: the first media of the `cover` collection. It is hidden from the
     * serialization, which carries the cover as the `cover` attribute and no other media, and it is never loaded by
     * default: the CRUD read loads it once for the whole result set, under the media permission and ACL of the
     * caller (see {@see IPreloadsAppendedRelations}).
     *
     * @return MorphOne<CmsMedia, $this>
     */
    public function coverMedia(): MorphOne
    {
        return $this->morphOne(CmsMedia::class, 'model')
            ->where('collection_name', 'cover')
            ->orderBy('order_column')
            ->orderBy('id');
    }

    /**
     * @return list<string>
     */
    public function appendedAttributeRelations(): array
    {
        return ['coverMedia'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('images');
        $this->addMediaCollection('videos');
        $this->addMediaCollection('audios');
        $this->addMediaCollection('files');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $conversions = [
            'high' => 75,
            'mid' => 50,
            'low' => 25,
        ];

        foreach ($conversions as $key => $quality) {
            $this->addMediaConversion('thumb-' . $key)
                ->performOnCollections('images', 'cover')
                ->width(300)
                ->height(300)
                ->sharpen(10)
                ->fit(Fit::Fill, 300, 300)
                ->optimize()
                ->quality($quality);
            $this->addMediaConversion('video_thumb-' . $key)
                ->performOnCollections('videos')
                ->extractVideoFrameAtSecond(2)
                ->width(300)
                ->height(300)
                ->sharpen(10)
                ->fit(Fit::Fill, 300, 300)
                ->optimize()
                ->quality($quality);
        }
    }

    /**
     * The first media of the `cover` collection, read under the media's own permission and ACL: null for a
     * caller who may not select media or whose media ACL hides it (spec 8.1, R6). A relation the CRUD read
     * preloaded is used as it is: it was loaded narrowed by that ACL, or left out when the caller may not read
     * media. Otherwise the relation is loaded here, narrowed the same way.
     *
     * @return Attribute<?Media, never>
     */
    protected function cover(): Attribute
    {
        return Attribute::make(
            get: function (): ?Media {
                if (! $this->relationLoaded('coverMedia')) {
                    if (! resolve(RelationAuthorizer::class)->loadForAppendedAttribute($this, 'coverMedia')) {
                        return null;
                    }

                    // With no acting user (console, queue) there is no caller to hide it from, and nothing was loaded.
                    $this->loadMissing('coverMedia');
                }

                $media = $this->getRelation('coverMedia');

                return $media instanceof CmsMedia ? $media : null;
            },
            // set: fn (Media $value) => $this->addMedia($value->getPath())->toMediaCollection('cover'),
        );
    }

    // private function commonThumbSizes(Conversion $conversion): void
    // {
    //     $conversion->width(300)
    //         ->height(300)
    //         ->sharpen(10)
    //         ->fit(Fit::Fill, 300, 300)
    //         ->optimize()
    //         ->quality(75)
    //         ->naming(static fn (string $fileName, string $extension): string => $fileName . '-high.' . $extension);
    //     $conversion->width(300)
    //         ->height(300)
    //         ->sharpen(10)
    //         ->fit(Fit::Fill, 300, 300)
    //         ->optimize()
    //         ->quality(50)
    //         ->naming(static fn (string $fileName, string $extension): string => $fileName . '-mid.' . $extension);
    //     $conversion->width(300)
    //         ->height(300)
    //         ->sharpen(10)
    //         ->fit(Fit::Fill, 300, 300)
    //         ->optimize()
    //         ->quality(25)
    //         ->naming(static fn (string $fileName, string $extension): string => $fileName . '-low.' . $extension);
    // }
}
