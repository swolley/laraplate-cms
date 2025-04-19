<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use Modules\Cms\Models\Field;

trait HasDynamicContents
{
    #[\Override]
    public function __get($key)
    {
        if ($this->hasAttribute($key) || method_exists(self::class, $key)) {
            return parent::__get($key);
        }

        return data_get($this->getComponentsAttribute(), $key);
    }

    #[\Override]
    public function __set($key, $value)
    {
        $components = $this->getComponentsAttribute();
        if (array_key_exists($key, $components)) {
            $components[$key] = $value;
            $this->setComponentsAttribute($components);
            return;
        }

        parent::__set($key, $value);

        if ($key === 'preset_id' && $value) {
            $this->entity_id = $this->preset?->entity_id;
        }
    }

    protected function getComponentsAttribute(): array
    {
        return $this->mergeComponentsValues(json_decode((string) $this->attributes['components'], true));
    }

    protected function setComponentsAttribute(array $components): void
    {
        $this->attributes['components'] = json_encode($this->mergeComponentsValues($components));
    }

    private function mergeComponentsValues(array $components): array
    {
        return $this->fields()->mapWithKeys(fn(Field $field) => [$field->name => data_get($components, $field->name) ?? $field->default])->toArray();
    }

    protected function dynamicSlugFields(): array
    {
        return $this->preset?->fields()
            ->select(['name', 'is_slug'])
            ->where('is_slug', true)
            ->pluck('name')
            ->toArray();
    }

    public function toArray(): array
    {
        $content = method_exists(parent::class, 'toArray') ? parent::toArray() : $this->attributesToArray();
        if (isset($content['components'])) {
            $components = $content['components'];
            unset($content['components']);
            return array_merge($content, $components);
        }

        return array_merge($content, $this->getComponentsAttribute());
    }
}
