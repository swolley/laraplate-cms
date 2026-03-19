<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

trait HasTranslations
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $translations = [];

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
        $this->translations[$locale] = $values;

        foreach ($values as $field => $value) {
            $this->attributes[$field] = $value;
        }

        return $this;
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
