<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Cms\Database\Factories\CategoryFactory;
use Modules\Cms\Helpers\HasMultimedia;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasTags;
use Modules\Cms\Helpers\HasTranslatedDynamicContents;
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
 * @mixin \Modules\Core\Helpers\HasTranslations
 * @mixin \Modules\Cms\Helpers\HasDynamicContents
 * @mixin \Modules\Cms\Helpers\HasTranslatedDynamicContents
 * @mixin \Modules\Core\Helpers\HasValidations
 * @mixin \Modules\Core\Helpers\SoftDeletes
 * @mixin \Modules\Cms\Helpers\HasSlug
 * @mixin \Modules\Cms\Helpers\HasPath
 * @mixin \Modules\Core\Helpers\SortableTrait
 * @mixin \Spatie\EloquentSortable\SortableTrait
 *
 * @method void setHighestOrderNumber() Set the highest order number
 * @method int getHighestOrderNumber() Get the highest order number
 * @method int getLowestOrderNumber() Get the lowest order number
 * @method \Illuminate\Database\Eloquent\Builder scopeOrdered(\Illuminate\Database\Eloquent\Builder $query, string $direction = 'asc') Scope to order by order column
 * @method static void setNewOrder(array|\ArrayAccess $ids, int $startOrder = 1, ?string $primaryKeyColumn = null, ?callable $modifyQuery = null) Set new order for multiple models
 * @method bool shouldSortWhenCreating() Check if should sort when creating
 * @method string determineOrderColumnName() Determine the order column name
 * @method \Illuminate\Database\Eloquent\Builder buildSortQuery() Build query for sorting
 *
 * @mixin IdeHelperCategory
 */
final class Category extends Model implements IMediable, Sortable
{
    // region Traits
    use HasActivation {
        HasActivation::casts as private activationCasts;
    }
    use HasApprovals {
        HasApprovals::toArray as private approvalsToArray;
        HasApprovals::requiresApprovalWhen as private requiresApprovalWhenTrait;
    }
    use HasFactory;
    use HasLocks;
    use HasMultimedia;
    use HasPath;
    use HasRecursiveRelationships;
    use HasTags;
    use HasTranslatedDynamicContents {
        HasTranslatedDynamicContents::getRules as private getRulesDynamicContents;
        HasTranslatedDynamicContents::toArray as private translatedDynamicContentsToArray;
        HasTranslatedDynamicContents::casts as private translatedDynamicContentsCasts;
    }
    use HasValidations {
        HasValidations::getRules as private getRulesTrait;
    }
    use HasValidity;
    use HasVersions;
    use SoftDeletes;
    use SortableTrait {
        SortableTrait::scopeOrdered as private scopePriorityOrdered;
    }
    // endregion

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'parent_id',
        'presettable_id',
        'entity_id',
        'logo',
        'logo_full',
    ];

    protected $hidden = [
        'entity',
        'parent_id',
        // 'model_type_id',
        'presettable_id',
        'entity_id',
        'created_at',
        'updated_at',
        'ancestorsAndSelf',
        'ancestors',
        'descendants',
        'descendantsAndSelf',
        'childrenAndSelf',
        'bloodline',
    ];

    /**
     * The contents that belong to the category.
     *
     * @return BelongsToMany<Content>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'categorizables')->using(Categorizable::class)->withTimestamps();
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $dynamic_fields = $this->getRulesDynamicContents();
        $default_rule = self::DEFAULT_RULE;
        $rules[$default_rule] = array_merge($rules[$default_rule], $dynamic_fields, [
            'parent_id' => 'sometimes|nullable|exists:categories,id',
        ]);
        $rules['create'] = array_merge($rules['create'], [
            // 'name' => 'required|string|max:255', // Validated in translation
            // 'slug' => 'sometimes|nullable|string|max:255', // Validated in translation
            'translations' => 'sometimes|array',
            'translations.*.locale' => 'required|string|max:10',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.slug' => 'sometimes|nullable|string|max:255',
            'translations.*.components' => 'sometimes|array',
        ]);
        $rules['update'] = array_merge($rules['update'], [
            // 'name' => 'sometimes|required|string|max:255', // Validated in translation
            // 'slug' => 'sometimes|nullable|string|max:255', // Validated in translation
            'translations' => 'sometimes|array',
            'translations.*.locale' => 'required|string|max:10',
            'translations.*.name' => 'sometimes|required|string|max:255',
            'translations.*.slug' => 'sometimes|nullable|string|max:255',
            'translations.*.components' => 'sometimes|array',
        ]);

        return $rules;
    }

    #[Override]
    public function getPath(): string
    {
        // Use slug from translation and build a stable ancestor chain
        return $this->buildAncestorChain('slug', '/');
    }

    public function appendPaths(): self
    {
        $this->appends = array_merge($this->appends, ['ids', 'path', 'full_name']);

        return $this;
    }

    #[Override]
    public function toArray(): array
    {
        $parsed = parent::toArray() ?? $this->attributesToArray();

        return array_merge($parsed, $this->translatedDynamicContentsToArray(), $this->approvalsToArray($parsed));
    }

    /**
     * Eager-load ancestors (and their translations) for path-related accessors.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithAncestorsForPath(Builder $query): Builder
    {
        return $query->with([
            'ancestors',
            'ancestors.translations',
        ]);
    }

    #[Override]
    protected static function booted(): void
    {
        self::addGlobalScope('global_filters', static function (Builder $query): void {
            /** @var Builder<static> $query */
            $query->active()->valid();
        });
        self::addGlobalScope('global_ordered', static function (Builder $query): void {
            /** @var Builder<static> $query */
            $query->ordered();
        });
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        // In global scope, $this is not available, so use $query->getModel() to get instance
        $model = $query->getModel();
        $orderColumn = $model->qualifyColumn($model->determineOrderColumnName());

        return $query->orderBy($orderColumn, 'asc')->validityOrdered();
    }

    protected function casts(): array
    {
        return array_merge($this->activationCasts(), $this->translatedDynamicContentsCasts(), [
            'parent_id' => 'integer',
            'model_type_id' => 'integer',
            'order' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
            'content' => 'json',
        ]);
    }

    protected function slugPlaceholders(): array
    {
        // Use name from translation
        return [...array_map(fn (string $field) => '{' . $field . '}', $this->dynamicSlugFields()), '{name}'];
    }

    protected function requiresApprovalWhen(array $modifications): bool
    {
        return $this->requiresApprovalWhenTrait($modifications) && ($modifications[self::$valid_from_column] ?? $modifications[self::$valid_to_column] ?? false);
    }

    protected function ids(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->buildAncestorChain('id', '.'),
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->buildAncestorChain('name', ' > '),
        );
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name,
        );
    }

    /**
     * Build a concatenated chain from ancestors plus the current model.
     */
    private function buildAncestorChain(string $field, string $separator): string
    {
        $ancestors = $this->ancestors;

        if (! $ancestors instanceof \Illuminate\Support\Collection) {
            $ancestors = collect();
        }

        /** @var \Illuminate\Support\Collection<int, mixed> $segments */
        $segments = $ancestors
            ->pluck($field)
            ->filter(static fn ($value): bool => $value !== null && $value !== '')
            ->reverse()
            ->values();

        $current = $this->{$field} ?? null;

        if ($current !== null && $current !== '') {
            $segments->push($current);
        }

        if ($segments->isEmpty()) {
            return (string) ($current ?? '');
        }

        return $segments->join($separator);
    }
}
