<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Contracts\Taggable;
use Modules\Cms\Database\Factories\ContentFactory;
use Modules\Cms\Helpers\HasMultimedia;
use Modules\Cms\Helpers\HasTags;
use Modules\Cms\Models\Pivot\Categorizable;
use Modules\Cms\Models\Pivot\Contributable;
use Modules\Cms\Models\Pivot\Locatable;
use Modules\Cms\Models\Pivot\Relatable;
use Modules\Cms\Observers\ContentObserver;
use Modules\Core\Contracts\IDynamicEntityTypable;
use Modules\Core\Helpers\HasApprovals;
use Modules\Core\Helpers\HasPath;
use Modules\Core\Helpers\HasTranslatedDynamicContents;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Helpers\HasValidity;
use Modules\Core\Helpers\HasVersions;
use Modules\Core\Helpers\LocaleContext;
use Modules\Core\Helpers\SoftDeletes;
use Modules\Core\Helpers\SortableTrait;
use Modules\Core\Locking\HasOptimisticLocking;
use Modules\Core\Locking\Traits\HasLocks;
use Modules\Core\Search\Schema\FieldDefinition;
use Modules\Core\Search\Schema\FieldType;
use Modules\Core\Search\Schema\IndexType;
use Modules\Core\Search\Traits\Searchable;
use Override;
use Spatie\EloquentSortable\Sortable;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int|string $id
 */
#[ObservedBy(ContentObserver::class)]
final class Content extends Model implements HasMedia, Sortable, Taggable
{
    // region Traits
    use HasApprovals {
        HasApprovals::toArray as private approvalsToArray;
        HasApprovals::requiresApprovalWhen as private requiresApprovalWhenTrait;
    }

    /** @use HasFactory<ContentFactory> */
    use HasFactory;
    use HasLocks;
    use HasMultimedia;
    use HasOptimisticLocking;
    use HasPath;
    use HasTags;
    use HasTranslatedDynamicContents {
        HasTranslatedDynamicContents::getRules as private getRulesTranslatedDynamicContents;
        HasTranslatedDynamicContents::toArray as private translatedDynamicContentsToArray;
        HasTranslatedDynamicContents::casts as private translatedDynamicContentsCasts;
    }
    use HasValidations {
        HasValidations::getRules as private getRulesTrait;
    }
    use HasValidity;
    use HasVersions;
    use Searchable {
        Searchable::toSearchableArray as private toSearchableArrayTrait;
        Searchable::getSearchMapping as private getSearchMappingTrait;
    }
    use SoftDeletes;
    use SortableTrait {
        SortableTrait::scopeOrdered as private scopePriorityOrdered;
    }
    // endregion

    public static array $childTypes = [];

    #[Override]
    protected $hidden = [
        'created_at',
        'updated_at',
        'withCaching',
        'withoutObjectCaching',
    ];

    /**
     * @var list<string>
     */
    private array $embed = ['title', 'textual_only'];

    /**
     * @var array<string, mixed>
     */
    private array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    public static function getEntityModelClass(): string
    {
        return Entity::class;
    }

    public static function makeFromEntity(Entity|string|int $entity): static
    {
        $entity_id = null;

        if (is_int($entity)) {
            $entity_id = self::fetchAvailableEntities(EntityType::CONTENTS)->firstWhere('id', $entity)?->id;
        } elseif (is_string($entity)) {
            $entity_id = self::fetchAvailableEntities(EntityType::CONTENTS)->firstWhere('slug', $entity)?->id;
        } else {
            $entity_id = array_key_exists($entity->id, self::$childTypes) ? $entity->id : null;
        }

        if (in_array($entity_id, ['', '0', 0, null], true)) {
            throw new InvalidArgumentException('Invalid entity: ' . json_encode($entity));
        }

        if ($entity instanceof Entity && $entity->type !== EntityType::CONTENTS) {
            throw new InvalidArgumentException('Invalid entity type for content: ' . $entity->type->value);
        }

        $presettable = self::fetchAvailablePresettables(EntityType::CONTENTS)->firstWhere('entity_id', $entity_id);

        throw_unless($presettable, InvalidArgumentException::class, 'No presettable found for entity: ' . $entity);

        return new self::$childTypes[$entity_id]([
            'presettable_id' => $presettable->id,
            'entity_id' => $entity_id,
        ]);
    }

    public static function makeWithDefaults(array $attributes = []): static
    {
        $model = new self($attributes);
        $model->setDefaultEntityAndPreset();

        return $model;
    }

    /**
     * The folders that belong to the content.
     *
     * @return BelongsToMany<Category, $this, Categorizable, 'pivot'>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'categorizables')->using(Categorizable::class)->withTimestamps();
    }

    /**
     * The locations that belong to the content.
     *
     * @return BelongsToMany<Location, $this, Locatable, 'pivot'>
     */
    public function locations(): BelongsToMany
    {
        $relation = $this->belongsToMany(Location::class, 'locatables')->using(Locatable::class)->withTimestamps();
        $relation->withTrashed();

        return $relation;
    }

    /**
     * The contributors that belong to the content.
     *
     * @return BelongsToMany<Contributor, $this, Contributable, 'pivot'>
     */
    public function contributors(): BelongsToMany
    {
        return $this->belongsToMany(Contributor::class, 'contributables')->using(Contributable::class)->withTimestamps();
    }

    /**
     * The related contents that belong to the content.
     *
     * @return BelongsToMany<Content, $this, Relatable, 'pivot'>
     */
    public function related(?bool $withInverse = false): BelongsToMany
    {
        $relation = $this->belongsToMany(self::class, 'relatables')->using(Relatable::class)->withTimestamps();

        if ($withInverse === true) {
            $relation->orWhere(fn (Builder $query) => $query->where('related_content_id', $this->id));
        }

        return $relation;
    }

    public function toSearchableArray(): array
    {
        // TODO: transform splitted values into objects? (contributors, categories, tags, locations)
        $document = $this->toSearchableArrayTrait();
        // $document['entity'] = $this->entity->name;
        // $document['entity_id'] = $this->entity->id;
        $document['preset'] = $this->preset->name;
        // $document['preset_id'] = $this->preset->id;
        $document['contributors'] = $this->contributors->map(fn (Contributor $contributor) => $contributor->only(['id', 'name', 'slug', 'path']))->values()->all();
        $document['categories'] = $this->categories->map(fn (Category $category) => $category->only(['id', 'name', 'slug', 'path']))->values()->all();
        $document['tags'] = $this->tags->map(fn (Tag $tag) => $tag->only(['id', 'name', 'slug', 'path']))->values()->all();
        $document['locations'] = $this->locations->map(fn (Location $location) => $location->only([
            'id',
            'name',
            'slug',
            'path',
            'address',
            'city',
            'province',
            'country',
            'postcode',
            'zone',
        ]))->values()->all();
        $document['type'] = $this->type;

        // Load all translations for indexing
        $translations = $this->translations;
        $default_locale = config('app.locale');
        $available_locales = LocaleContext::getAvailable();

        // Add base fields with default translation (for compatibility/fallback)
        $default_translation = $translations->firstWhere('locale', $default_locale);

        if ($default_translation) {
            $document['slug'] = $default_translation->slug;
            $document['title'] = $default_translation->title;

            // Add default components
            if (isset($default_translation->components)) {
                foreach ($default_translation->components as $field => $value) {
                    $document[$field] = gettype($value) === 'string' ? Str::replaceMatches('/\\n|\\r|\\t/', '', $value) : $value;
                }
            }
        }

        // Add fields for each locale (title_locale, slug_locale, components_locale)
        foreach ($available_locales as $locale) {
            $translation = $translations->firstWhere('locale', $locale);

            if ($translation) {
                $document['title_' . $locale] = $translation->title;
                $document['slug_' . $locale] = $translation->slug;
            }
        }

        return $document;
    }

    public function getSearchMapping(): array
    {
        $schema = $this->getSchemaDefinition();
        $schema->addField(new FieldDefinition('entity', FieldType::KEYWORD, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::FACETABLE]));
        $schema->addField(new FieldDefinition('preset', FieldType::KEYWORD, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::FACETABLE]));
        $schema->addField(new FieldDefinition('contributors', FieldType::ARRAY, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::FACETABLE], [
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
            ],
        ]));
        $schema->addField(new FieldDefinition('type', FieldType::KEYWORD, [IndexType::SEARCHABLE, IndexType::FILTERABLE]));
        $schema->addField(new FieldDefinition('valid_from', FieldType::DATE, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::SORTABLE]));
        $schema->addField(new FieldDefinition('valid_to', FieldType::DATE, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::SORTABLE]));
        $schema->addField(new FieldDefinition('is_deleted', FieldType::BOOLEAN, [IndexType::SEARCHABLE, IndexType::FILTERABLE, IndexType::FACETABLE]));
        $schema->addField(new FieldDefinition('embedding', FieldType::VECTOR, [IndexType::SEARCHABLE, IndexType::VECTOR]));

        // Base fields with default translation (for compatibility/fallback)
        $schema->addField(new FieldDefinition('slug', FieldType::KEYWORD, [IndexType::SEARCHABLE]));
        $schema->addField(new FieldDefinition('title', FieldType::TEXT, [IndexType::SEARCHABLE, IndexType::FILTERABLE]));

        // Add fields for each locale
        $available_locales = LocaleContext::getAvailable();
        $translations = $this->translations;
        $default_translation = $translations->firstWhere('locale', config('app.locale'));

        // Get component fields from default translation
        $component_fields = [];

        if ($default_translation && isset($default_translation->components)) {
            $component_fields = array_keys($default_translation->components);
        }

        foreach ($available_locales as $locale) {
            // Add title and slug for each locale
            $schema->addField(new FieldDefinition('title_' . $locale, FieldType::TEXT, [IndexType::SEARCHABLE, IndexType::FILTERABLE]));
            $schema->addField(new FieldDefinition('slug_' . $locale, FieldType::KEYWORD, [IndexType::SEARCHABLE]));
        }

        // Add base component fields (from default translation)
        foreach ($component_fields as $field) {
            $schema->addField(new FieldDefinition($field, FieldType::TEXT, [IndexType::SEARCHABLE]));
        }

        return $this->getSearchMappingTrait($schema);
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $fields = $this->getRulesTranslatedDynamicContents();
        $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], $fields);
        $rules['create'] = array_merge($rules['create'], [
            // 'title' => 'required|string|max:255', // Validated in translation
            // 'slug' => 'sometimes|nullable|string|max:255', // Validated in translation
            'entity_id' => 'required|exists:entities,id',
            'presettable_id' => 'required|exists:presettables,id',
            'translations' => 'sometimes|array',
            'translations.*.locale' => 'required|string|max:10',
            'translations.*.title' => 'required|string|max:255',
            'translations.*.slug' => 'sometimes|nullable|string|max:255',
            'translations.*.components' => 'sometimes|array',
        ]);
        $rules['update'] = array_merge($rules['update'], [
            // 'title' => 'sometimes|required|string|max:255', // Validated in translation
            // 'slug' => 'sometimes|nullable|string|max:255', // Validated in translation
            'entity_id' => 'sometimes|required|exists:entities,id',
            'presettable_id' => 'sometimes|required|exists:presettables,id',
            'translations' => 'sometimes|array',
            'translations.*.locale' => 'required|string|max:10',
            'translations.*.title' => 'sometimes|required|string|max:255',
            'translations.*.slug' => 'sometimes|nullable|string|max:255',
            'translations.*.components' => 'sometimes|array',
        ]);

        return $rules;
    }

    public function getPathPrefix(): string
    {
        $entity = $this->entity;

        return $entity !== null ? $entity->slug : '';
    }

    #[Override]
    public function getPath(): ?string
    {
        return $this->categories->first()?->getPath();
    }

    #[Override]
    public function toArray(): array
    {
        $parsed = parent::toArray();

        return array_merge($parsed, $this->translatedDynamicContentsToArray(), $this->approvalsToArray($parsed));
    }

    /**
     * Set default entity and preset based on class name.
     */
    public function setDefaultEntityAndPreset(): void
    {
        $class_name = class_basename(self::class);
        $entity_name = Str::lower($class_name);

        // Find entity by name
        $entity = self::fetchAvailableEntities(EntityType::CONTENTS)->firstWhere('name', $entity_name);

        if (! $entity) {
            return;
        }

        // Only set if not already set
        $this->entity_id = $entity->id;
        $this->setRelation('entity', $entity);

        // Find first available preset for this entity
        $presettable = self::fetchAvailablePresettables(EntityType::CONTENTS)
            ->firstWhere('entity_id', $entity->id);

        if (! $presettable) {
            return;
        }

        $this->presettable_id = $presettable->id;
        $this->setRelation('presettable', $presettable);
    }

    #[Override]
    protected static function getEntityType(): IDynamicEntityTypable
    {
        return EntityType::CONTENTS;
    }

    protected static function booted(): void
    {
        self::addGlobalScope('global_filters', static function (Builder $query): void {
            /** @var Builder<Content> $query */
            $query->valid();
        });
        self::addGlobalScope('global_ordered', static function (Builder $query): void {
            /** @var Builder<Content> $query */
            $query->ordered();
        });
    }

    protected static function newFactory(): ContentFactory
    {
        return ContentFactory::new();
    }

    /**
     * Override components accessor to coordinate HasTranslations and HasDynamicContents.
     * When components is translatable, get it from translation and merge with defaults.
     */
    protected function components(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                // components is a translatable field, get it from HasTranslations
                $raw_components = $this->translationsGet('components');

                // If we got components from translation, merge with defaults using HasDynamicContents
                if ($raw_components !== null) {
                    return $this->mergeComponentsValues($raw_components);
                }

                // Fallback: if no translation, merge empty array with defaults
                return $this->mergeComponentsValues([]);
            },
            set: function (array $components): void {
                // components is a translatable field, save it through HasTranslations
                $this->translationsSet('components', $components);
            },
        );
    }

    protected function slugPlaceholders(): array
    {
        // Use title from translation
        return [...array_map(fn (string $field): string => '{' . $field . '}', $this->dynamicSlugFields()), '{title}'];
    }

    protected function requiresApprovalWhen(array $modifications): bool
    {
        return $this->requiresApprovalWhenTrait($modifications) && ($modifications[self::$valid_from_column] ?? $modifications[self::$valid_to_column] ?? false);
    }
}
