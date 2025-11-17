<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\Rule;
use Modules\Cms\Database\Factories\CategoryFactory;
use Modules\Cms\Helpers\HasDynamicContents;
use Modules\Cms\Helpers\HasMultimedia;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Cms\Helpers\HasTags;
use Modules\Cms\Models\Pivot\Categorizable;
use Modules\Core\Helpers\HasActivation;
use Modules\Core\Helpers\HasApprovals;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Helpers\HasValidity;
use Modules\Core\Helpers\HasVersions;
use Modules\Core\Helpers\SoftDeletes;
use Modules\Core\Helpers\SortableTrait;
use Modules\Core\Locking\Traits\HasLocks;
use Override;
use Spatie\EloquentSortable\Sortable;
use Spatie\MediaLibrary\HasMedia as IMediable;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

/**
 * @mixin IdeHelperCategory
 */
final class Category extends Model implements IMediable, Sortable
{
    use HasApprovals {
        HasApprovals::toArray as protected approvalsToArray;
    }
    use HasDynamicContents {
        HasDynamicContents::getRules as protected getRulesDynamicContents;
        HasDynamicContents::toArray as protected dynamicContentsToArray;
    }
    use HasFactory;
    use HasLocks;
    use HasMultimedia;
    use HasPath;
    use HasRecursiveRelationships;
    use HasSlug;
    use HasTags;
    use HasValidations {
        HasValidations::getRules as protected getRulesTrait;
    }
    use HasValidity;
    use HasActivation;
    use HasVersions;
    use SoftDeletes;
    use SortableTrait {
        SortableTrait::scopeOrdered as protected scopePriorityOrdered;
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'persistence',
        'logo',
        'logo_full',
    ];

    //    protected $with = [
    //        'entity',
    //    ];

    protected $hidden = [
        'entity',
        'parent_id',
        'model_type_id',
        'persistence',
        'created_at',
        'updated_at',
        'ancestorsAndSelf',
        'ancestors',
        'descendants',
        'descendantsAndSelf',
        'childrenAndSelf',
        'bloodline',
    ];

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
        $rules = $this->getRulesTrait();
        $fields = $this->getRulesDynamicContents();
        $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], $fields);
        $rules['create'] = array_merge($rules['create'], [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(fn ($query) => $query->where('parent_id', request('parent_id'))->whereNull('deleted_at')),
            ],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories')->where(fn ($query) => $query->where('parent_id', request('parent_id'))->whereNull('deleted_at'))->ignore($this->id, 'id'),
            ],
        ]);

        return $rules;
    }

    #[Override]
    public function getPath(): string
    {
        return $this->ancestors->pluck('slug')->reverse()->merge([$this->slug])->join('/');
    }

    public function appendPaths(): self
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
        self::addGlobalScope('global_filters', function (Builder $query): void {
            $query->active()->valid();
        });
        self::addGlobalScope('global_ordered', function (Builder $query): void {
            $query->ordered();
        });
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    // region Scopes

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->priorityOrdered()->validityOrdered();
    }

    #[Override]
    protected function casts(): array
    {
        return array_merge($this->activationCasts(), $this->dynamicContentsCasts(), [
            'parent_id' => 'integer',
            'model_type_id' => 'integer',
            'order' => 'integer',
            'persistence' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
            'content' => 'json',
        ]);
    }

    protected function slugFields(): array
    {
        return [...$this->dynamicSlugFields(), 'name'];
    }

    protected function requiresApprovalWhen(array $modifications): bool
    {
        return $this->requiresApprovalWhenTrait($modifications) && ($modifications[self::$valid_from_column] ?? $modifications[self::$valid_to_column] ?? false);
    }

    // endregion

    // region Attributes

    protected function ids(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ancestors->pluck('id')->reverse()->merge([$this->id])->join('.'),
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ancestors->pluck('name')->reverse()->merge([$this->name])->join(' > '),
        );
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name,
        );
    }
}
