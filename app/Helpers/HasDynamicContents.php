<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Field;
use Modules\Cms\Models\Preset;
use UnexpectedValueException;

/**
 * Trait for models that have dynamic contents.
 *
 * @property array<string, mixed> $components
 * @property-read ?string $type
 * @property-read ?Entity $entity
 * @property-read ?Preset $preset
 * @property ?int $entity_id
 * @property ?int $preset_id
 */
trait HasDynamicContents
{
    public function __get($key)
    {
        if ($this->hasAttribute($key) || method_exists(self::class, $key) || in_array($key, $this->fillable, true) || $key === 'pivot') {
            return parent::__get($key);
        }

        return data_get($this->getComponentsAttribute(), $key);
    }

    public function __set($key, $value): void
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

    public static function fetchAvailableEntities(EntityType $type): Collection
    {
        return Cache::memo()->rememberForever(
            new Entity()->getCacheKey(),
            fn(): Collection => Entity::query()->withoutGlobalScopes()->get(),
        )->where('type', $type);
    }

    public static function fetchAvailablePresets(EntityType $type): Collection
    {
        return Cache::memo()->rememberForever(
            new Preset()->getCacheKey(),
            fn(): Collection => Preset::withoutGlobalScopes()->with(['fields', 'entity'])->get(),
        )->where('entity.type', $type);
    }

    public function initializeHasDynamicContents(): void
    {
        if (! in_array('components', $this->hidden, true)) {
            $this->hidden[] = 'components';
        }

        if (! in_array('components', $this->fillable, true)) {
            $this->fillable[] = 'components';
        }

        if (! in_array('entity_id', $this->fillable, true)) {
            $this->fillable[] = 'entity_id';
        }

        if (! in_array('preset_id', $this->fillable, true)) {
            $this->fillable[] = 'preset_id';
        }

        if (! isset($this->attributes['components'])) {
            $this->attributes['components'] = '{}';
        }

        if (! in_array('type', $this->appends, true)) {
            $this->appends[] = 'type';
        }

        if (! in_array('entity_id', $this->hidden, true)) {
            $this->hidden[] = 'entity_id';
        }

        if (! in_array('preset_id', $this->hidden, true)) {
            $this->hidden[] = 'preset_id';
        }

        if (! in_array('preset', $this->hidden, true)) {
            $this->hidden[] = 'preset';
        }

        if (! in_array('entity', $this->hidden, true)) {
            $this->hidden[] = 'entity';
        }

        if (! in_array('entity', $this->with, true)) {
            $this->with[] = 'entity';
        }

        if (! in_array('preset', $this->with, true)) {
            $this->with[] = 'preset';
        }
    }

    /**
     * The entity that belongs to the content.
     *
     * @return BelongsTo<Entity>
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * The preset that belongs to the content.
     *
     * @return BelongsTo<Preset>
     */
    public function preset(): BelongsTo
    {
        /** @var BelongsTo<Preset> $relation */
        $relation = $this->belongsTo(Preset::class, ['preset_id', 'entity_id'], ['id', 'entity_id']);
        $relation->withTrashed();

        return $relation;
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

    public function getRules(): array
    {
        $fields = [];

        foreach ($this->fields() as $field) {
            $rule = $field->type->getRule();

            if ($field->pivot->is_required) {
                $rule .= '|required';

                if ($field->type === FieldType::ARRAY) {
                    $rule .= '|filled';
                }
            } else {
                $rule .= '|nullable';
            }

            if (isset($field->options->min)) {
                $rule .= '|min:' . $field->options->min;
            }

            if (isset($field->options->max)) {
                $rule .= '|max:' . $field->options->max;
            }
            $fields[$field->name] = mb_trim((string) $rule, '|');

            if ($field->type === FieldType::EDITOR) {
                $fields[$field->name . '.blocks'] = 'array';
                $fields[$field->name . '.blocks.*.type'] = 'string';
                $fields[$field->name . '.blocks.*.data'] = 'array';
            }
        }

        return $fields;
    }

    protected static function bootHasDynamicContents(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&HasDynamicContents $model */
            if ($model->preset) {
                $model->preset_id = $model->preset->id;

                if ($model->entity_id && $model->entity_id !== $model->preset->entity_id) {
                    throw new UnexpectedValueException("Entity mismatch: {$model->entity->name} is not compatible with {$model->preset->name}");
                }
                $model->entity_id = $model->preset->entity_id;
            } elseif (! $model->preset_id) {
                $model->preset_id = static::fetchAvailablePresets(EntityType::tryFrom($model->getTable()))->firstOrFail()->id;
            }
        });
    }

    protected function type(): Attribute
    {
        return new Attribute(
            get: function () {
                if ($this->relationLoaded('preset')) {
                    return $this->preset?->entity?->name;
                }

                if ($this->preset_id) {
                    return static::fetchAvailablePresets(EntityType::tryFrom($this->getTable()))->firstWhere('id', $this->preset_id)?->entity?->name;
                }

                return null;
            },
        );
    }

    protected function getComponentsAttribute(): array
    {
        return isset($this->attributes['components'])
            ? $this->mergeComponentsValues(json_decode((string) $this->attributes['components'], true))
            : [];
    }

    protected function setComponentsAttribute(array $components): void
    {
        $this->attributes['components'] = json_encode($this->mergeComponentsValues($components));
    }

    protected function dynamicSlugFields(): array
    {
        return $this->preset?->fields()
            ->select(['name', 'is_slug'])
            ->where('is_slug', true)
            ->pluck('name')
            ->toArray();
    }

    /**
     * The fields that belong to the content.
     *
     * @return Collection<Field>
     */
    private function fields(): Collection
    {
        return $this->preset?->fields ?? new Collection();
    }

    private function mergeComponentsValues(array $components): array
    {
        return $this->fields()->mapWithKeys(fn(Field $field) => [$field->name => data_get($components, $field->name) ?? $field->pivot->default])->toArray();
    }
}
