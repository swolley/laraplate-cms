<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            if (! $model->getRawOriginal('slug') && ! $model->isDirty('slug')) {
                $model->slug = $model->generateSlug();
            }
        });
    }

    public static function slugFields(): array
    {
        return ['name'];
    }

    public function initializeHasSlug(): void
    {
        if (! in_array('slug', $this->fillable, true)) {
            $this->fillable[] = 'slug';
        }
    }

    public function generateSlug(): string
    {
        $slugger = config('cms.slugger', '\Illuminate\Support\Str::slug');
        $slug = array_reduce($this->slugValues(), fn ($slug, $value): string => $slug . '-' . ($value ? mb_trim((string) $value) : ''), '');

        return call_user_func($slugger, mb_ltrim($slug, '-'));
    }

    protected function slugValues(): array
    {
        return array_map(fn ($name) => $this->{$name}, $this->slugFields());
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['slug'] ?? $this->generateSlug(),
        );
    }
}
