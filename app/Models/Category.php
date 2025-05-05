<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Override;
use Illuminate\Validation\Rule;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Cms\Helpers\HasTags;
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
final class Category extends ComposhipsModel implements Sortable
{
    use HasApprovals,
        HasDynamicContents,
        HasFactory,
        HasLocks,
        HasPath,
        HasRecursiveRelationships,
        HasSlug,
        HasTags,
        HasValidations,
        HasValidity,
        HasVersions,
        SoftDeletes,
        SortableTrait {
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

    // region Scopes

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    public function ordered(Builder $query): Builder
    {
        return $query->priorityOrdered()->validityOrdered();
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    public function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // endregion

    // region Relations

    /**
     * The entity that belongs to the category.
     *
     * @return BelongsTo<Entity>
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * The contents that belong to the category.
     *
     * @return BelongsToMany<Content>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'categorizables')->using(Categorizable::class)->withTimestamps();
    }

    // endregion

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
            $fields[$field->name] = mb_trim((string) $rule, '|');
        }

        $rules = $this->getRulesTrait();
        $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], $fields);
        $rules['create'] = array_merge($rules['create'], [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(fn($query) => $query->where(['parent_id' => request('parent_id'), 'deleted_at' => null])),
            ],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories')->where(fn($query) => $query->where(['parent_id' => request('parent_id'), 'deleted_at' => null]))->ignore($this->id, 'id'),
            ],
        ]);

        return $rules;
    }

    #[Override]
    public function getPath(): string
    {
        return $this->ancestors->pluck('slug')->reverse()->merge([$this->slug])->join('/');
    }

    public function appendPaths(): static
    {
        $this->appends = array_merge($this->appends, ['ids', 'path', 'full_name']);

        return $this;
    }

    #[Override]
    public function toArray(): array
    {
        return array_merge($this->dynamicContentsToArray(), $this->approvalsToArray());
    }

    #[Override]
    protected static function booted(): void
    {
        self::saving(function (Category $category): void {
            if (! $category->parent_id) {
                $category->parent_entity_id = $category->parent?->entity_id;
            }
        });

        self::addGlobalScope('global_filters', function (Builder $query): void {
            $query->active()->valid()->ordered();
        });
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    #[Override]
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

    // public function scopeForEntity(Builder $query, string|int|Entity|null $entity): Builder
    // {
    //     // null is a value for non completely filled models
    //     return $query->whereHas('contents', fn($q) => is_string($entity) ? $q->where('name', $entity) : ($q->where('entity_id', is_int($entity) ? $entity : $entity->id)));
    // }

    // endregion

    // region Attributes

    private function ids(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->ancestors->pluck('id')->reverse()->merge([$this->id])->join('.'),
        );
    }

    private function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->ancestors->pluck('name')->reverse()->merge([$this->name])->join(' > '),
        );
    }

    protected function slugFields(): array
    {
        return [...$this->dynamicSlugFields(), 'name'];
    }

    protected function requiresApprovalWhen($modifications): bool
    {
        return $this->requiresApprovalWhenTrait($modifications) && ($modifications[self::$valid_from_column] ?? $modifications[self::$valid_to_column] ?? false);
    }
}
