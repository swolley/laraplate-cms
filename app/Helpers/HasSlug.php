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
            if (in_array('slug', $model->getFillable(), true)) {
                // If model uses HasTranslations, slug is in translation
                if (method_exists($model, 'getRelationValue')) {
                    $translation = $model->getRelationValue('translation');

                    // If translation exists and slug is not set, generate it
                    if ($translation && ! isset($translation->slug)) {
                        $model->slug = $model->generateSlug();
                    } elseif (! $translation) {
                        // No translation yet, generate slug (will be saved in translation via HasTranslations)
                        $model->slug = $model->generateSlug();
                    }
                } elseif (! isset($model->attributes['slug']) && ! $model->isDirty('slug')) {
                    // Fallback for models without translations
                    $model->slug = $model->generateSlug();
                }
            }
        });
    }

    public static function slugFields(): array
    {
        return ['name'];
    }

    public function initializeHasSlug(): void
    {
        // Slug is now in translations table, don't add to fillable
    }

    public function generateSlug(): string
    {
        $slugger = config('cms.slugger', \Illuminate\Support\Str::class . '::slug');
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
            get: function () {
                // If model uses HasTranslations, get slug from translation
                if (method_exists($this, 'getRelationValue') && $this->relationLoaded('translation')) {
                    $translation = $this->getRelationValue('translation');

                    if ($translation && isset($translation->slug)) {
                        return $translation->slug;
                    }
                }

                // Generate slug if not exists
                return $this->attributes['slug'] ?? $this->generateSlug();
            },
        );
    }
}
