<?php

namespace Modules\Cms\Models;

use Illuminate\Validation\Rule;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Awobaz\Compoships\Compoships;
use Modules\Core\Helpers\HasValidity;
use Modules\Core\Helpers\HasVersions;
use Spatie\EloquentSortable\Sortable;
use Modules\Core\Helpers\HasApprovals;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Helpers\HasValidations;
use Spatie\EloquentSortable\SortableTrait;
use Modules\Cms\Models\Pivot\Categorizable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Cms\Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

/**
 * @mixin IdeHelperCategory
 */
class Category extends Model implements Sortable
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
        HasValidations,
        Compoships {
        getRules as protected getRulesTrait;
        getFullPath as protected getFullPathTrait;
        HasRecursiveRelationships::newBaseQueryBuilder insteadof Compoships;
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'entity_id',
        'parent_id',
        'name',
        'slug',
        'short_content',
        'content',
        'model_type_id',
        'order',
        'persistence',
        'logo',
        'logo_full',
        'is_active',
    ];

    protected $hidden = [
        'entity_id',
        'parent_id',
        'model_type_id',
        'order',
        'persistence',
        'is_active',
        'created_at',
        'updated_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
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
        static::saving(function (Category $category) {
            if ($category->isDirty('parent_id')) {
                // if ($category->entity_id && $category->entity_id !== $category->parent->entity_id) {
                //     throw new \UnexpectedValueException("Entity mismatch: {$category->entity->name} is not compatible with {$category->parent->name}");
                // }
                $category->entity_id = $category->parent->entity_id;
            }
        });
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

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
        return $this->belongsToMany(Content::class, 'categorizables', ['category_id', 'entity_id'], ['id', 'entity_id'])->using(Categorizable::class)->withTimestamps();
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        // $rules[static::DEFAULT_RULE] = array_merge($rules[static::DEFAULT_RULE], [
        //     'name' => ['required', 'string', 'max:255'],
        // ]);
        $rules['create'] = array_merge($rules['create'], [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(function ($query) {
                    $query->where(['parent_id' => request('parent_id'), 'entity_id' => request('entity_id'), 'deleted_at' => null]);
                })
            ],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories')->where(function ($query) {
                    $query->where(['parent_id' => request('parent_id'), 'entity_id' => request('entity_id'), 'deleted_at' => null]);
                })->ignore($this->id, 'id')
            ],
        ]);
        return $rules;
    }

    #[\Override]
    public function getPath(): ?string
    {
        $ancestors = $this->ancestors()->pluck('slug');
        return $ancestors->join('/');
    }
}
