<?php

declare(strict_types=1);

namespace Modules\CMS\Helpers;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    public function initializeHasMultiMedia(): void
    {
        if (! in_array('cover', $this->appends, true)) {
            $this->appends[] = 'cover';
        }
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
     * caller who may not select media or whose media ACL hides it (spec 8.1, R6). The media relation it reads
     * is loaded narrowed by that ACL.
     *
     * @return Attribute<?Media, never>
     */
    protected function cover(): Attribute
    {
        return Attribute::make(
            get: function (): ?Media {
                if (! resolve(RelationAuthorizer::class)->loadForAppendedAttribute($this, 'media')) {
                    return null;
                }

                $media = $this->getFirstMedia('cover');

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
