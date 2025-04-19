<?php

namespace Modules\Cms\Models;

use Illuminate\Validation\Rule;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Core\Helpers\HasValidity;
use Modules\Core\Helpers\HasVersions;
use Modules\Core\Helpers\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Modules\Core\Helpers\HasApprovals;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Locking\Traits\HasLocks;
use Spatie\EloquentSortable\SortableTrait;
use Modules\Cms\Helpers\HasDynamicContents;
use Modules\Cms\Models\Pivot\Categorizable;
use Modules\Core\Overrides\ComposhipsModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Modules\Cms\Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

/**
 * @mixin IdeHelperCategory
 */
class Category extends ComposhipsModel implements Sortable
{
    use HasFactory,
        HasRecursiveRelationships,
        SoftDeletes,
        HasValidity,
        HasApprovals,
        HasVersions,
        SortableTrait,
        HasSlug,
        HasPath,
        HasLocks,
        HasValidations,
        HasDynamicContents {
        getRules as protected getRulesTrait;
        getFullPath as protected getFullPathTrait;
        requiresApprovalWhen as protected requiresApprovalWhenTrait;
        HasDynamicContents::toArray as protected dynamicContentsToArray;
        HasApprovals::toArray as protected approvalsToArray;
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'preset_id',
        'entity_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'persistence',
        'logo',
        'logo_full',
        'is_active',
    ];

    protected $with = [
        'entity',
    ];

    protected $hidden = [
        'entity_id',
        'entity',
        'parent_id',
        'parent_entity_id',
        'model_type_id',
        'persistence',
        'is_active',
        'created_at',
        'updated_at',
        'ancestorsAndSelf',
        'ancestors',
        'descendants',
        'descendantsAndSelf',
        'childrenAndSelf',
        'bloodline',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'components' => 'json',
            'preset_id' => 'integer',
            'entity_id' => 'integer',
            'parent_id' => 'integer',
            'model_type_id' => 'integer',
            'order' => 'integer',
            'persistence' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
            'content' => 'json',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if ($category->isDirty('parent_id')) {
                if ($category->entity_id !== $category->parent->entity_id) {
                    throw new \UnexpectedValueException("Entity mismatch: {$category->entity->name} is not compatible with {$category->parent->name}");
                } else {
                    $category->parent_entity_id = $category->parent?->entity_id;
                }
            }
        });
        static::updating(function (Category $category) {
            if ($category->isDirty('parent_id')) {
                if (!$category->parent_id) {
                    $category->parent_entity_id = null;
                } else if ($category->entity_id !== $category->parent->entity_id) {
                    throw new \UnexpectedValueException("Entity mismatch: {$category->entity->name} is not compatible with {$category->parent->name}");
                }
                $category->parent_entity_id = $category->parent->entity_id;
            }
        });

        static::addGlobalScope('global_filters', function (Builder $query) {
            $query->active()->valid()->ordered();
        });
    }

    #region Scopes

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->priorityOrdered()->validityOrdered();
    }

    protected function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    protected function scopeForEntity(Builder $query, null|int|Entity $entity): Builder
    {
        return $query->where(function ($query) use ($entity) {
            if ($entity) {
                $query->where('entity_id', is_int($entity) ? $entity : $entity->id)->orWhereNull('entity_id');
            } else {
                $query->whereNull('entity_id');
            }
        });
    }

    #endregion

    #region Attributes

    protected function ids(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->ancestors->pluck('id')->reverse()->merge([$this->id])->join('.'),
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->ancestors->pluck('name')->reverse()->merge([$this->name])->join(' > '),
        );
    }

    #endregion

    #region Relations

    /**
     * The entity that belongs to the category.
     * @return BelongsTo<Entity>
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * The contents that belong to the category.
     * @return BelongsToMany<Content>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(
            Content::class,
            'categorizables',
            ['category_id', 'entity_id'],
            ['content_id', 'entity_id'],
            ['id', 'entity_id'],
            ['id', 'entity_id']
        )->using(Categorizable::class)->withTimestamps();
    }

    #endregion

    public function getRules(): array
    {
        $fields = [];
        foreach ($this->fields() as $field) {
            $rule = $field->type->getRule();
            if ($field->required) {
                $rule .= '|required';
            }
            if (isset($field->options->min)) {
                $rule .= '|min:' . $field->options->min;
            }
            if (isset($field->options->max)) {
                $rule .= '|max:' . $field->options->max;
            }
            $fields[$field->name] = trim((string) $rule, '|');
        }

        $rules = $this->getRulesTrait();
        $rules[static::DEFAULT_RULE] = array_merge($rules[static::DEFAULT_RULE], $fields);
        $rules['create'] = array_merge($rules['create'], [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(fn($query) => $query->where(['parent_id' => request('parent_id'), 'entity_id' => request('entity_id'), 'deleted_at' => null]))
            ],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories')->where(fn($query) => $query->where(['parent_id' => request('parent_id'), 'entity_id' => request('entity_id'), 'deleted_at' => null]))->ignore($this->id, 'id')
            ],
        ]);
        return $rules;
    }


    #[\Override]
    public function getPath(): string
    {
        return $this->ancestors->pluck('slug')->reverse()->merge([$this->slug])->join('/');
    }

    public function appendPaths(): static
    {
        $this->appends = array_merge($this->appends, ['ids', 'path', 'full_name']);

        return $this;
    }

    protected function slugFields(): array
    {
        return [...$this->dynamicSlugFields(), 'name'];
    }

    protected function requiresApprovalWhen($modifications): bool
    {
        return $this->requiresApprovalWhenTrait($modifications) && ($modifications[static::$valid_from_column] ?? $modifications[static::$valid_to_column] ?? false);
    }

    #[\Override]
    public function toArray(): array
    {
        return array_merge($this->dynamicContentsToArray(), $this->approvalsToArray());
    }
}
