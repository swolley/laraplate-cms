<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Modules\Core\Services\Translation\Definitions\ITranslated;

/**
 * Test stub mirroring Core {@see HasTranslations} persistence and accessors
 * without LocaleScope or domain events (standalone CMS / Testbench runs).
 *
 * Must not declare a $translations property (shadows the translations() relation).
 *
 * @template TTranslationModel of Model&ITranslated
 */
trait HasTranslations
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $pending_translations = [];

    protected ?string $current_setter_locale = null;

    /**
     * @var array<class-string, array<string>>
     */
    protected static array $cached_translatable_fields = [];

    public static function getTranslatableFields(): array
    {
        $model_class = static::class;

        if (! isset(static::$cached_translatable_fields[$model_class])) {
            static::$cached_translatable_fields[$model_class] = array_filter(
                (new (static::getTranslationModelClass()))->getFillable(),
                static fn (string $field): bool => $field !== 'locale' && ! str_ends_with($field, '_id'),
            );
        }

        return static::$cached_translatable_fields[$model_class];
    }

    public function isTranslatableField(string $field): bool
    {
        return in_array($field, $this::getTranslatableFields(), true);
    }

    public function getAttribute($key): mixed
    {
        if ($this->isTranslatableField($key)) {
            $value = $this->getTranslatableFieldValue($key);
            $accessor = 'get' . Str::studly($key) . 'Attribute';

            if (method_exists($this, $accessor)) {
                return $this->{$accessor}($value);
            }

            return $value;
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if ($this->isTranslatableField($key)) {
            $mutator = 'set' . Str::studly($key) . 'Attribute';

            if (method_exists($this, $mutator)) {
                $this->{$mutator}($value);

                return $this;
            }

            $this->setTranslatableFieldValue($key, $value);

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(static::getTranslationModelClass());
    }

    public function translation(): HasOne
    {
        $current_locale = LocaleContext::get();
        $default_locale = config('app.locale');
        $fallback_enabled = LocaleContext::isFallbackEnabled();

        $relation = $this->hasOne(static::getTranslationModelClass());

        if ($fallback_enabled) {
            $relation->where(function ($query) use ($current_locale, $default_locale): void {
                $query->where('locale', $current_locale)
                    ->orWhere('locale', $default_locale);
            })
                ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [$current_locale]);
        } else {
            $relation->where('locale', $current_locale);
        }

        return $relation;
    }

    public function inLocale(string $locale): self
    {
        $this->current_setter_locale = $locale;

        return $this;
    }

    public function getTranslation(?string $locale = null, ?bool $with_fallback = null): ?Model
    {
        $locale ??= LocaleContext::get();
        $default_locale = config('app.locale');
        $fallback_enabled = $with_fallback ?? LocaleContext::isFallbackEnabled();

        $translation = $this->translations()->where('locale', $locale)->first();

        if (! $translation && $fallback_enabled && $locale !== $default_locale) {
            return $this->translations()->where('locale', $default_locale)->first();
        }

        return $translation;
    }

    public function setTranslation(string $locale, array $data): self
    {
        $translation = $this->translations()->where('locale', $locale)->first();

        if ($translation) {
            $translation->update($data);
        } else {
            $this->translations()->create(array_merge($data, ['locale' => $locale]));
        }

        if ($locale === LocaleContext::get()) {
            $this->load('translation');
        }

        return $this;
    }

    public function hasTranslation(?string $locale = null): bool
    {
        $locale ??= LocaleContext::get();

        return $this->translations()->where('locale', $locale)->exists();
    }

    /**
     * @return Collection<int, Model>
     */
    public function getAllTranslations(): Collection
    {
        return $this->translations;
    }

    public function toArray(?array $parsed = null): array
    {
        $content = $parsed ?? (method_exists(parent::class, 'toArray') ? parent::toArray() : $this->attributesToArray());
        $translation = $this->getRelationValue('translation');

        if ($translation) {
            foreach ($this::getTranslatableFields() as $field) {
                if (isset($translation->{$field}) && ! in_array($field, $this->hidden, true)) {
                    $content[$field] = $translation->{$field};
                }
            }
        }

        $locale = $this->getCurrentLocale();

        if (isset($this->pending_translations[$locale])) {
            return array_merge($content, $this->pending_translations[$locale]);
        }

        return $content;
    }

    public function initializeHasTranslations(): void
    {
        if (! in_array('translation', $this->hidden, true)) {
            $this->hidden[] = 'translation';
        }

        if (! in_array('translation', $this->with, true)) {
            $this->with[] = 'translation';
        }

        if (! in_array('locale', $this->appends, true)) {
            $this->appends[] = 'locale';
        }
    }

    protected static function bootHasTranslations(): void
    {
        static::saved(function (Model $model): void {
            /** @var Model&self $model */
            $model->savePendingTranslations();
        });
    }

    /**
     * @return class-string<TTranslationModel>
     */
    protected static function getTranslationModelClass(): string
    {
        $current_class = static::class;
        $parent_class = get_parent_class($current_class);

        if ($parent_class && $parent_class !== Model::class) {
            $current_class = $parent_class;
        }

        $class_name = str_replace('\\Models\\', '\\Models\\Translations\\', $current_class) . 'Translation';

        throw_unless(class_exists($class_name), Exception::class, 'Translation model class not found: ' . $class_name);

        throw_unless(is_subclass_of($class_name, ITranslated::class), Exception::class, 'Translation model class does not implement ITranslated: ' . $class_name);

        return $class_name;
    }

    protected function savePendingTranslations(): void
    {
        if ($this->pending_translations === []) {
            return;
        }

        foreach ($this->pending_translations as $locale => $fields) {
            $translation = $this->translations()->where('locale', $locale)->first();

            if ($translation) {
                $translation->update($fields);
            } else {
                $this->translations()->create(array_merge($fields, ['locale' => $locale]));
            }
        }

        $this->pending_translations = [];
        $this->current_setter_locale = null;

        $this->load('translation');
    }

    protected function getDefaultTranslation(): ?Model
    {
        return $this->translations()
            ->where('locale', config('app.locale'))
            ->first();
    }

    protected function getTranslatableFieldValue(string $key): mixed
    {
        $current_locale = LocaleContext::get();
        $default_locale = config('app.locale');
        $fallback_enabled = LocaleContext::isFallbackEnabled();

        if (isset($this->pending_translations[$current_locale][$key])) {
            return $this->pending_translations[$current_locale][$key];
        }

        if ($fallback_enabled && isset($this->pending_translations[$default_locale][$key])) {
            return $this->pending_translations[$default_locale][$key];
        }

        $translation = $this->getRelationValue('translation');

        if ($translation && isset($translation->{$key})) {
            return $translation->{$key};
        }

        if ($fallback_enabled) {
            $default_translation = $this->getDefaultTranslation();

            if ($default_translation && isset($default_translation->{$key})) {
                return $default_translation->{$key};
            }
        }

        return null;
    }

    protected function setTranslatableFieldValue(string $key, mixed $value): void
    {
        $locale = $this->getCurrentLocale();

        if (! isset($this->pending_translations[$locale])) {
            $this->pending_translations[$locale] = [];
        }

        $this->pending_translations[$locale][$key] = $value;
    }

    /**
     * Used by models (e.g. Content) with Attribute-based accessors for translatable JSON fields.
     */
    protected function translationsGet(string $key): mixed
    {
        return $this->getTranslatableFieldValue($key);
    }

    /**
     * Used by models (e.g. Content) with Attribute-based mutators for translatable JSON fields.
     */
    protected function translationsSet(string $key, mixed $value): void
    {
        $this->setTranslatableFieldValue($key, $value);
    }

    protected function locale(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getCurrentLocale(),
        );
    }

    private function getCurrentLocale(): string
    {
        return $this->current_setter_locale ?? LocaleContext::get();
    }
}
