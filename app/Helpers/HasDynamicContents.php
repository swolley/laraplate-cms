<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Field;
use Modules\Cms\Models\Pivot\Presettable;
use Modules\Cms\Models\Preset;
use Override;

/**
 * Trait for models that have dynamic contents.
 *
 * @property array<string, mixed> $components
 * @property-read ?string $type
 * @property-read ?Entity $entity
 * @property-read ?Preset $preset
 * @property ?int $entity_id
 * @property ?int $presettable_id
 */
trait HasDynamicContents
{
    public function __get($key)
    {
        if ($this->hasAttribute($key) || method_exists(self::class, $key) || in_array($key, $this->fillable, true) || $key === 'pivot') {
            return parent::__get($key);
        }

        if ($this->isDynamicField($key)) {
            return data_get($this->getComponentsAttribute(), $key);
        }

        return parent::__get($key);
    }

    public function __set($key, $value): void
    {
        if ($this->isDynamicField($key)) {
            $this->setComponentAttribute($key, $value);

            return;
        }

        parent::__set($key, $value);

        if ($key === 'presettable_id' && $value) {
            $this->entity_id = $this->presettable?->entity_id;
        }
    }

    /**
     * Fetch available entities for a given type.
     *
     * @return Collection<Entity>
     */
    public static function fetchAvailableEntities(EntityType $type): Collection
    {
        /** @phpstan-ignore staticMethod.notFound */
        return Cache::memo()->rememberForever(
            new Entity()->getCacheKey(),
            function (): Collection {
                return Entity::query()->withoutGlobalScopes()->orderBy('is_default', 'desc')->orderBy('name', 'asc')->get();
            },
        )->where('type', $type);
    }

    public static function fetchAvailablePresets(EntityType $type): Collection
    {
        /** @phpstan-ignore staticMethod.notFound */
        return Cache::memo()->rememberForever(
            new Preset()->getCacheKey(),
            fn (): Collection => Preset::query()->withoutGlobalScopes()->with(['fields', 'entity'])->orderBy('is_default', 'desc')->orderBy('name', 'asc')->get(),
        )->where('entity.type', $type);
    }

    public static function fetchAvailablePresettables(EntityType $type): Collection
    {
        return Cache::memo()->rememberForever(
            new Presettable()->getTable(),
            function (): Collection {
                return Presettable::query()->withoutGlobalScopes()
                    ->join('presets', 'presettables.preset_id', '=', 'presets.id')
                    ->join('entities', 'presets.entity_id', '=', 'entities.id')
                    ->addSelect('presettables.*', DB::raw('CASE WHEN presets.is_default THEN 1 ELSE 0 END + CASE WHEN entities.is_default THEN 1 ELSE 0 END as order_score'))
                    ->orderBy('order_score', 'desc')->get();
            },
        )->where('entity.type', $type);
    }

    /**
     * Override setAttribute to handle translatable fields.
     */
    public function setAttribute($key, $value)
    {
        if ($this->isDynamicField($key)) {
            $this->setComponentAttribute($key, $value);

            return $this;
        }

        return parent::setAttribute($key, $value);
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

        if (! in_array('presettable_id', $this->fillable, true)) {
            $this->fillable[] = 'presettable_id';
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

        if (! in_array('presettable_id', $this->hidden, true)) {
            $this->hidden[] = 'presettable_id';
        }

        if (! in_array('preset', $this->hidden, true)) {
            $this->hidden[] = 'preset';
        }

        if (! in_array('entity', $this->hidden, true)) {
            $this->hidden[] = 'entity';
        }

        if (! in_array('presettable', $this->with, true)) {
            $this->with[] = 'presettable';
        }

        if (! in_array('presettable', $this->hidden, true)) {
            $this->hidden[] = 'presettable';
        }
    }

    /**
     * Convenience relation to avoid multiple column foreign key references.
     *
     * @return BelongsTo<Presettable>
     */
    public function presettable(): BelongsTo
    {
        /** @var BelongsTo<Presettable> $relation */
        $relation = $this->belongsTo(Presettable::class);
        $relation->withTrashed();

        return $relation;
    }

    public function toArray(?array $parsed = null): array
    {
        $content = $parsed ?? (method_exists(parent::class, 'toArray') ? parent::toArray() : $this->attributesToArray());

        if (isset($content['components'])) {
            $components = $content['components'];
            unset($content['components']);

            return array_merge($content, $components);
        }

        return array_merge($content, $this->getComponentsAttribute());
    }

    public function getDynamicFields(): array
    {
        return $this->fields()->pluck('name')->toArray();
    }

    public function isDynamicField(string $field): bool
    {
        return in_array($field, $this->getDynamicFields(), true);
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

            $fields[$field->name] = mb_trim($rule, '|');

            if ($field->type === FieldType::EDITOR) {
                $fields[$field->name . '.blocks'] = 'array';
                $fields[$field->name . '.blocks.*.type'] = 'string';
                $fields[$field->name . '.blocks.*.data'] = 'array';
            }
        }

        return $fields;
    }

    public function setDefaultPresettable(): void
    {
        $first_available = static::fetchAvailablePresettables(EntityType::tryFrom($this->getTable()))->firstOrFail();
        $this->presettable_id = $first_available->id;
        $this->entity_id = $first_available->entity_id;
        $this->setRelation('presettable', $first_available);
    }

    protected static function bootHasDynamicContents(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&HasDynamicContents $model */
            if ($model->presettable) {
                $model->presettable_id = $model->presettable->id;
                $model->entity_id = $model->presettable->entity_id;
                $model->setRelation('presettable', $model->presettable);
            } else {
                $model->setDefaultPresettable();
            }
        });
    }

    /**
     * The entity that belongs to the content.
     *
     * @return Attribute<Entity|null>
     */
    protected function entity(): Attribute
    {
        return new Attribute(
            get: function () {
                $presettable = $this->presettable;

                if (! $presettable) {
                    $this->setDefaultPresettable();
                    $presettable = $this->presettable;
                }

                return $presettable?->entity;
            },
        );
    }

    /**
     * The preset that belongs to the content.
     *
     * @return Attribute<Preset|null>
     */
    protected function preset(): Attribute
    {
        return new Attribute(
            get: fn () => $this->presettable?->preset,
        );
    }

    protected function getTextualOnlyAttribute(): string
    {
        $accumulator = '';

        foreach ($this->fields() as $field) {
            if (! $field->type->isTextual()) {
                continue;
            }

            if ($field->type === FieldType::EDITOR) {
                $accumulator .= ' ' . implode(' ', Arr::pluck(((object) $this->{$field->name})->blocks, 'data.text'));
            } else {
                $accumulator .= ' ' . $this->{$field->name};
            }
        }

        return strip_tags($accumulator);
    }

    protected function type(): Attribute
    {
        return new Attribute(
            get: function () {
                if (! $this->relationLoaded('preset') && $this->presettable_id) {
                    $this->load('presettable');

                    return $this->entity?->name;
                }

                if ($this->presettable_id) {
                    return static::fetchAvailablePresets(EntityType::tryFrom($this->getTable()))->firstWhere('id', $this->presettable_id)?->entity?->name;
                }

                $this->setDefaultPresettable();

                return $this->entity?->name;
            },
        );
    }

    protected function getComponentsAttribute(): array
    {
        // Get components from model attributes (for models without translations)
        $components = isset($this->attributes['components'])
            ? json_decode((string) $this->attributes['components'], true)
            : [];

        return $this->mergeComponentsValues($components);
    }

    protected function setComponentsAttribute(array $components): void
    {
        // Store components in model attributes (for models without translations)
        $this->attributes['components'] = json_encode($this->mergeComponentsValues($components));
    }

    protected function dynamicSlugFields(): array
    {
        if (! $this->preset) {
            return [];
        }

        return $this->preset->fields()
            ->select(['name', 'is_slug'])
            ->where('is_slug', true)
            ->pluck('name')
            ->toArray();
    }

    protected function casts(): array
    {
        return [
            'components' => 'json',
            'entity_id' => 'integer',
            'presettable_id' => 'integer',
        ];
    }

    /**
     * Merge components with default values from fields.
     */
    protected function mergeComponentsValues(array $components): array
    {
        return $this->fields()
            ->mapWithKeys(fn (Field $field): array => [$field->name => data_get($components, $field->name, $field->pivot->default)])
            ->toArray();
    }

    private function setComponentAttribute(string $key, $value): void
    {
        $this->setComponentsAttribute([$key => $value]);
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
}
