<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Minimal stub aligned with the real trait: must not declare a $translations property
 * (that shadows the translations() relation and breaks static analysis).
 */
trait HasTranslations
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $pending_translations = [];

    /**
     * @return list<string>
     */
    public static function getTranslatableFields(): array
    {
        return [];
    }

    public static function isTranslatableField(string $field): bool
    {
        return in_array($field, static::getTranslatableFields(), true);
    }

    public function getAttribute($key): mixed
    {
        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        return parent::setAttribute($key, $value);
    }

    public function toArray(array $parsed = []): array
    {
        if ($parsed !== []) {
            return $parsed;
        }

        return parent::toArray();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setTranslation(string $locale, array $values): static
    {
        $this->pending_translations[$locale] = $values;

        foreach ($values as $field => $value) {
            $this->attributes[$field] = $value;
        }

        return $this;
    }

    /**
     * @return HasMany<Model>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(static::getTranslationModelClass());
    }

    /**
     * @return HasOne<Model>
     */
    public function translation(): HasOne
    {
        return $this->hasOne(static::getTranslationModelClass());
    }

    /**
     * Resolves the translation model the same way as Core HasTranslations::getTranslationModelClass().
     * Falls back to {@see Model} when the conventional class is missing (standalone analysis edge cases).
     *
     * @return class-string<Model>
     */
    protected static function getTranslationModelClass(): string
    {
        $current_class = static::class;
        $parent_class = get_parent_class($current_class);

        if ($parent_class !== false && $parent_class !== Model::class) {
            $current_class = $parent_class;
        }

        $class_name = str_replace('\\Models\\', '\\Models\\Translations\\', $current_class) . 'Translation';

        if (class_exists($class_name)) {
            return $class_name;
        }

        return Model::class;
    }

    protected function getTranslatableFieldValue(string $key): mixed
    {
        return null;
    }

    protected function setTranslatableFieldValue(string $key, mixed $value): void {}

    protected function translationsGet(string $key): mixed
    {
        return null;
    }

    protected function translationsSet(string $key, mixed $value): void {}
}
