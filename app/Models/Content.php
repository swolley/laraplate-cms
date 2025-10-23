<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Database\Factories\ContentFactory;
use Modules\Cms\Helpers\HasDynamicContents;
use Modules\Cms\Helpers\HasMultimedia;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Cms\Helpers\HasTags;
use Modules\Cms\Models\Pivot\Authorable;
use Modules\Cms\Models\Pivot\Categorizable;
use Modules\Cms\Models\Pivot\Locatable;
use Modules\Cms\Models\Pivot\Relatable;
use Modules\Core\Helpers\HasApprovals;
use Modules\Core\Helpers\HasChildren;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Helpers\HasValidity;
use Modules\Core\Helpers\HasVersions;
use Modules\Core\Helpers\SoftDeletes;
use Modules\Core\Helpers\SortableTrait;
use Modules\Core\Locking\HasOptimisticLocking;
use Modules\Core\Locking\Traits\HasLocks;
use Modules\Core\Overrides\ComposhipsModel;
use Modules\Core\Search\Schema\FieldDefinition;
use Modules\Core\Search\Schema\FieldType;
use Modules\Core\Search\Schema\IndexType;
use Modules\Core\Search\Traits\Searchable;
use Override;
use Spatie\EloquentSortable\Sortable;
use Spatie\MediaLibrary\HasMedia;

/**
 * @mixin IdeHelperContent
 */
class Content extends ComposhipsModel implements HasMedia, Sortable
{
    use HasApprovals {
        HasApprovals::toArray as protected approvalsToArray;
    }
    use HasChildren {
        HasChildren::hasMany as protected hasChildrenHasMany;
        HasChildren::belongsTo as protected hasChildrenBelongsTo;
        HasChildren::belongsToMany as protected hasChildrenBelongsToMany;
    }
    use HasDynamicContents {
        HasDynamicContents::getRules as protected getRulesDynamicContents;
        HasDynamicContents::toArray as protected dynamicContentsToArray;
    }
    use HasFactory;
    use HasLocks;
    use HasMultimedia;
    use HasOptimisticLocking;
    use HasPath;
    use HasSlug;
    use HasTags;
    use HasValidations {
        HasValidations::getRules as protected getRulesTrait;
    }
    use HasValidity;
    use HasVersions;
    use Searchable;
    use SoftDeletes;
    use SortableTrait {
        SortableTrait::scopeOrdered as protected scopePriorityOrdered;
    }

    public static array $childTypes = [];

    protected $fillable = [
        'title',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'withCaching',
        'withoutObjectCaching',
    ];

    protected string $childColumn = 'entity_id';

    protected array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    protected $appends = [
        'path',
    ];

    protected $embed = ['title', 'textual_only'];

    public static function resolveChildTypes(): void
    {
        self::$childTypes = [];

        foreach (self::fetchAvailableEntities(EntityType::CONTENTS) as $entity) {
            $class_name = Str::studly($entity->name);
            $full_class_name = 'Modules\\Cms\\Models\\Contents\\' . $class_name;

            if (! class_exists($full_class_name)) {
                $class_definition = file_get_contents(module_path('Cms', 'stubs/content.stub'));
                $class_definition = str_replace(['$CLASS$', '<?php'], [$class_name, ''], $class_definition);

                // generate the class at runtime
                eval($class_definition);
            }

            self::$childTypes[$entity->id] = $full_class_name;
        }
    }

    public static function makeFromEntity(Entity|string|int $entity): static
    {
        $entity_id = null;

        if (is_int($entity)) {
            $entity_id = array_key_exists($entity, self::getChildTypes()) ? $entity : null;
        } elseif (is_string($entity)) {
            $entity_id = array_key_first(array_filter(self::getChildTypes(), fn ($class) => Str::endsWith($class, '\\' . Str::studly($entity))));
        } elseif (is_object($entity) && array_key_exists($entity->id, self::getChildTypes())) {
            $entity_id = $entity->id;
        }

        throw_if(in_array($entity_id, ['', '0', 0, null], true) || $entity->type !== EntityType::CONTENTS, InvalidArgumentException::class, 'Invalid entity: ' . $entity);

        $preset_id = self::fetchAvailablePresets(EntityType::CONTENTS)->firstWhere('entity_id', $entity_id)?->id;

        throw_unless($preset_id, InvalidArgumentException::class, 'No preset found for entity: ' . $entity);

        return new self::$childTypes[$entity_id](['preset_id' => $preset_id, 'entity_id' => $entity_id]);
    }

    // endregion

    // region Relations

    /**
     * The folders that belong to the content.
     *
     * @return BelongsToMany<Category>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'categorizables')->using(Categorizable::class)->withTimestamps();
    }

    /**
     * The locations that belong to the content.
     *
     * @return BelongsToMany<Location>
     */
    public function locations(): BelongsToMany
    {
        $relation = $this->belongsToMany(Location::class, 'locatables')->using(Locatable::class)->withTimestamps();
        $relation->withTrashed();

        return $relation;
    }

    /**
     * The authors that belong to the content.
     *
     * @return BelongsToMany<Author>
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'authorables')->using(Authorable::class)->withTimestamps();
    }

    /**
     * The related contents that belong to the content.
     *
     * @return BelongsToMany<Content>
     */
    public function related(?bool $withInverse = false): BelongsToMany
    {
        $relation = $this->belongsToMany(self::class, 'relatables')->using(Relatable::class)->withTimestamps();

        if ($withInverse === true) {
            $relation->orWhere(fn ($query) => $query->where('related_content_id', $this->id));
        }

        return $relation;
    }

    // endregion

    public function toSearchableArray(): array
    {
        // TODO: transform splitted values into objects? (authors, categories, tags, locations)
        $document = $this->toSearchableArrayTrait();
        // $document['entity'] = $this->entity->name;
        // $document['entity_id'] = $this->entity->id;
        $document['preset'] = $this->preset->name;
        // $document['preset_id'] = $this->preset->id;
        $document['authors'] = $this->authors->only(['id', 'name', 'slug', 'path'])->toArray();
        // $document['authors_id'] = $this->authors->pluck('id')->toArray();
        $document['categories'] = $this->categories->only(['name', 'id', 'slug', 'path'])->toArray();
        // $document['categories_id'] = $this->categories->pluck('id')->toArray();
        $document['tags'] = $this->tags->only(['name', 'id', 'slug', 'path'])->toArray();
        // $document['tags_id'] = $this->tags->pluck('id')->toArray();
        $document['locations'] = (object) $this->locations->only(['id', 'name', 'slug', 'path', 'address', 'city', 'province', 'country', 'postcode', 'zone', 'geolocation'])->toArray();
        // $document['location_id'] = $this->locations->pluck('id')->toArray();
        $document['slug'] = $this->slug;
        $document['type'] = $this->type;
        $document['title'] = $this->title;

        foreach ($this->components as $field => $value) {
            $document[$field] = gettype($value) === 'string' ? Str::replaceMatches('/\\n|\\r|\\t/', '', $value) : $value;
        }

        return $document;
    }

    public function getSearchMapping(): array
    {
        $schema = $this->getSchemaDefinition();
        $schema->addField(new FieldDefinition('entity', FieldType::KEYWORD, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::FACETABLE]));
        $schema->addField(new FieldDefinition('preset', FieldType::KEYWORD, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::FACETABLE]));
        $schema->addField(new FieldDefinition('authors', FieldType::ARRAY, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::FACETABLE], [
            'properties' => [
                'name' => FieldType::TEXT,
                'id' => FieldType::INTEGER,
                'slug' => FieldType::KEYWORD,
                'path' => FieldType::KEYWORD,
            ],
        ]));
        $schema->addField(new FieldDefinition('categories', FieldType::ARRAY, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::FACETABLE], [
            'properties' => [
                'name' => FieldType::TEXT,
                'id' => FieldType::INTEGER,
                'slug' => FieldType::KEYWORD,
                'path' => FieldType::KEYWORD,
            ],
        ]));
        $schema->addField(new FieldDefinition('tags', FieldType::ARRAY, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::FACETABLE], [
            'properties' => [
                'name' => FieldType::KEYWORD,
                'id' => FieldType::INTEGER,
                'slug' => FieldType::KEYWORD,
                'path' => FieldType::KEYWORD,
            ],
        ]));
        $schema->addField(new FieldDefinition('locations', FieldType::ARRAY, [IndexType::SEARCHABLE, IndexType::FILTERABLE], [
            'properties' => [
                'name' => FieldType::TEXT,
                'id' => FieldType::INTEGER,
                'slug' => FieldType::KEYWORD,
                'path' => FieldType::KEYWORD,
                'address' => FieldType::TEXT,
                'city' => FieldType::KEYWORD,
                'province' => FieldType::KEYWORD,
                'country' => FieldType::KEYWORD,
                'postcode' => FieldType::KEYWORD,
                'zone' => FieldType::KEYWORD,
                'geolocation' => FieldType::GEOCODE,
            ],
        ]));
        $schema->addField(new FieldDefinition('slug', FieldType::KEYWORD, [IndexType::SEARCHABLE]));
        $schema->addField(new FieldDefinition('type', FieldType::KEYWORD, [IndexType::SEARCHABLE, IndexType::FILTERABLE]));
        $schema->addField(new FieldDefinition('valid_from', FieldType::DATE, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::SORTABLE]));
        $schema->addField(new FieldDefinition('valid_to', FieldType::DATE, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::SORTABLE]));
        $schema->addField(new FieldDefinition('is_deleted', FieldType::BOOLEAN, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::FACETABLE]));
        $schema->addField(new FieldDefinition('title', FieldType::TEXT, [IndexType::SEARCHABLE, IndexType::FILTERABLE]));
        $schema->addField(new FieldDefinition('embedding', FieldType::VECTOR, [IndexType::SEARCHABLE, IndexType::VECTOR]));

        foreach ($this->components as $field => $value) {
            $schema->addField(new FieldDefinition($field, FieldType::TEXT, [IndexType::SEARCHABLE]));
        }

        return $this->getSearchMappingTrait($schema);
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $fields = $this->getRulesDynamicContents();
        $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], $fields);
        $rules['create'] = array_merge($rules['create'], [
            'title' => 'required|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255',
            'entity_id' => 'required|exists:entities,id',
            'preset_id' => 'required|exists:presets,id',
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255',
            'entity_id' => 'sometimes|required|exists:entities,id',
            'preset_id' => 'sometimes|required|exists:presets,id',
        ]);

        return $rules;
    }

    public function getPathPrefix(): string
    {
        return $this->entity?->slug ?? '';
    }

    #[Override]
    public function getPath(): ?string
    {
        return $this->categories->first()?->getPath();
    }

    #[Override]
    public function toArray(): array
    {
        return array_merge($this->dynamicContentsToArray(), $this->approvalsToArray());
    }

    protected static function getChildTypes(bool $forceRefresh = false): array
    {
        if (static::$childTypes === [] || $forceRefresh) {
            static::resolveChildTypes();
        }

        return static::$childTypes;
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('global_filters', function (Builder $query): void {
            $query->valid();
        });
        static::addGlobalScope('global_ordered', function (Builder $query): void {
            $query->ordered();
        });
    }

    protected static function newFactory(): ContentFactory
    {
        $factory = ContentFactory::new();

        // if ensure that the factory is created for the correct derived entity
        if (static::class !== self::class) {
            $factory->state(fn (): array => [
                'entity_id' => Entity::query()
                    ->where('name', Str::lower(class_basename(static::class)))
                    ->where('type', EntityType::CONTENTS)
                    ->firstOrFail()
                    ->id,
            ]);
        }

        return $factory;
    }

    // region Scopes

    /**
     * Order contents by priority and validity.
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->priorityOrdered()->validityOrdered()->orderBy($this->qualifyColumn(Model::CREATED_AT), 'desc');
    }

    /**
     * Filter contents by entity.
     */
    #[Scope]
    protected function forEntity(Builder $query, Entity $entity): Builder
    {
        return $query->where('entity_id', $entity->id);
    }

    // TODO: how to extract embedding dynamic contents?
    // protected $embed = ['components'];

    #[Override]
    protected function casts(): array
    {
        return [
            'components' => 'json',
            'preset_id' => 'integer',
            'entity_id' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }

    // endregion

    // region Attributes

    protected function slugFields(): array
    {
        return [...$this->dynamicSlugFields(), 'title'];
    }

    protected function requiresApprovalWhen(array $modifications): bool
    {
        return $this->requiresApprovalWhenTrait($modifications) && ($modifications[static::$valid_from_column] ?? $modifications[static::$valid_to_column] ?? false);
    }
}
