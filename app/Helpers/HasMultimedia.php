<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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

    protected function cover(): Attribute
    {
        return Attribute::make(
            get: fn(): ?Media => $this->getFirstMedia('cover'),
            set: fn($value): Media => $this->addMedia($value)->toMediaCollection('cover'),
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
    //         ->naming(fn(string $fileName, string $extension): string => $fileName . '-high.' . $extension);
    //     $conversion->width(300)
    //         ->height(300)
    //         ->sharpen(10)
    //         ->fit(Fit::Fill, 300, 300)
    //         ->optimize()
    //         ->quality(50)
    //         ->naming(fn(string $fileName, string $extension): string => $fileName . '-mid.' . $extension);
    //     $conversion->width(300)
    //         ->height(300)
    //         ->sharpen(10)
    //         ->fit(Fit::Fill, 300, 300)
    //         ->optimize()
    //         ->quality(25)
    //         ->naming(fn(string $fileName, string $extension): string => $fileName . '-low.' . $extension);
    // }
}
