<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Database\Factories\ContentFactory;
use Modules\Cms\Helpers\HasMultimedia;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Cms\Helpers\HasTags;
use Modules\Cms\Helpers\HasTranslatedDynamicContents;
use Modules\Cms\Models\Pivot\Authorable;
use Modules\Cms\Models\Pivot\Categorizable;
use Modules\Cms\Models\Pivot\Locatable;
use Modules\Cms\Models\Pivot\Relatable;
use Modules\Core\Helpers\HasApprovals;
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
 * @mixin IdeHelperContent
 * @mixin \Modules\Core\Helpers\HasTranslations
 * @mixin \Modules\Cms\Helpers\HasDynamicContents
 * @mixin \Modules\Cms\Helpers\HasTranslatedDynamicContents
 * @mixin \Modules\Core\Helpers\HasValidations
 * @mixin \Modules\Core\Helpers\HasApprovals
 * @mixin \Modules\Core\Helpers\HasVersions
 * @mixin \Modules\Core\Helpers\HasValidity
 * @mixin \Modules\Core\Helpers\SoftDeletes
 * @mixin \Modules\Core\Search\Traits\Searchable
 * @mixin \Modules\Cms\Helpers\HasSlug
 * @mixin \Modules\Cms\Helpers\HasPath
 * @mixin \Modules\Cms\Helpers\HasTags
 * @mixin \Modules\Cms\Helpers\HasMultimedia
 * @mixin \Modules\Core\Locking\Traits\HasLocks
 * @mixin \Modules\Core\Locking\HasOptimisticLocking
 * @mixin \Modules\Core\Helpers\SortableTrait
 */
final class Content extends Model implements HasMedia, Sortable
{
    // region Traits
    use HasApprovals {
        HasApprovals::toArray as private approvalsToArray;
        HasApprovals::requiresApprovalWhen as private requiresApprovalWhenTrait;
    }
    use HasFactory;
    use HasLocks;
    use HasMultimedia;
    use HasOptimisticLocking;
    use HasPath;
    use HasSlug;
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
    use Searchable;
    use SoftDeletes;
    use SortableTrait {
        SortableTrait::scopeOrdered as private scopePriorityOrdered;
    }
    // endregion

    public static array $childTypes = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'withCaching',
        'withoutObjectCaching',
    ];

    protected array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    protected $embed = ['title', 'textual_only'];

    /**
     * Handle __get to merge translations and dynamic contents.
     */
    public function __get($key)
    {
        // First check translatable fields (cache to avoid recursion)
        $translatable_fields = $this->getTranslatableFields();

        if (in_array($key, $translatable_fields, true)) {
            return $this->translationsGet($key);
        }

        // Then check dynamic contents
        return $this->dynamicContentsGet($key);
    }

    /**
     * Handle __set to merge translations and dynamic contents.
     */
    public function __set($key, $value): void
    {
        // First check translatable fields (cache to avoid recursion)
        $translatable_fields = $this->getTranslatableFields();

        if (in_array($key, $translatable_fields, true)) {
            $this->translationsSet($key, $value);

            return;
        }

        // Then check dynamic contents
        $this->dynamicContentsSet($key, $value);
    }

    public static function makeFromEntity(Entity|string|int $entity): static
    {
        $entity_id = null;

        if (is_int($entity)) {
            $entity_id = self::fetchAvailableEntities(EntityType::CONTENTS)->firstWhere('id', $entity)?->id;
        } elseif (is_string($entity)) {
            $entity_id = self::fetchAvailableEntities(EntityType::CONTENTS)->firstWhere('slug', $entity)?->id;
        } elseif (is_object($entity) && array_key_exists($entity->id, self::getChildTypes())) {
            $entity_id = $entity->id;
        }

        throw_if(in_array($entity_id, ['', '0', 0, null], true) || $entity->type !== EntityType::CONTENTS, InvalidArgumentException::class, 'Invalid entity: ' . $entity);

        $preset_id = self::fetchAvailablePresets(EntityType::CONTENTS)->firstWhere('entity_id', $entity_id)?->id;

        throw_unless($preset_id, InvalidArgumentException::class, 'No preset found for entity: ' . $entity);

        return new self::$childTypes[$entity_id](['preset_id' => $preset_id, 'entity_id' => $entity_id]);
    }

    public static function makeWithDefaults(array $attributes = []): static
    {
        $model = new static($attributes);
        $model->setDefaultEntityAndPreset();

        return $model;
    }

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

                // Add components for this locale
                if (isset($translation->components)) {
                    foreach ($translation->components as $field => $value) {
                        $field_name = $field . '_' . $locale;
                        $document[$field_name] = gettype($value) === 'string' ? Str::replaceMatches('/\\n|\\r|\\t/', '', $value) : $value;
                    }
                }
            }
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

            // Add component fields for each locale
            foreach ($component_fields as $field) {
                $schema->addField(new FieldDefinition($field . '_' . $locale, FieldType::TEXT, [IndexType::SEARCHABLE]));
            }
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
            'title' => 'required|string|max:255', // Validated in translation
            'slug' => 'sometimes|nullable|string|max:255', // Validated in translation
            'entity_id' => 'required|exists:entities,id',
            'preset_id' => 'required|exists:presets,id',
            'translations' => 'sometimes|array',
            'translations.*.locale' => 'required|string|max:10',
            'translations.*.title' => 'required|string|max:255',
            'translations.*.slug' => 'sometimes|nullable|string|max:255',
            'translations.*.components' => 'sometimes|array',
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'title' => 'sometimes|required|string|max:255', // Validated in translation
            'slug' => 'sometimes|nullable|string|max:255', // Validated in translation
            'entity_id' => 'sometimes|required|exists:entities,id',
            'preset_id' => 'sometimes|required|exists:presets,id',
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
        $parsed = parent::toArray() ?? $this->attributesToArray();

        return array_merge($parsed, $this->translatedDynamicContentsToArray($parsed), $this->approvalsToArray($parsed));
    }

    /**
     * Set default entity and preset based on class name.
     */
    public function setDefaultEntityAndPreset(): void
    {
        $class_name = class_basename(static::class);
        $entity_name = Str::lower($class_name);

        // Find entity by name
        $entity = static::fetchAvailableEntities(EntityType::CONTENTS)->firstWhere('name', $entity_name);

        if (! $entity) {
            return;
        }

        // Only set if not already set
        $this->entity_id = $entity->id;
        $this->setRelation('entity', $entity);

        // Find first available preset for this entity
        $presettable = static::fetchAvailablePresettables(EntityType::CONTENTS)
            ->firstWhere('entity_id', $entity->id);

        if (! $presettable) {
            return;
        }

        $this->presettable_id = $presettable->id;
        $this->setRelation('presettable', $presettable);
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        // Auto-assign entity and preset for child classes based on class name
        static::creating(function (Model $model): void {
            /** @var Content $model */
            // Only auto-assign if not already set and this is a child class
            if (static::class !== self::class && ($model->entity_id === null || $model->presettable_id === null)) {
                $model->setDefaultEntityAndPreset();
            }
        });
    }

    protected static function booted(): void
    {
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
                    return $this->mergeComponentsValues($raw_components ?? []);
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

    protected function slugFields(): array
    {
        // Use title from translation
        return [...$this->dynamicSlugFields(), 'title'];
    }

    protected function requiresApprovalWhen(array $modifications): bool
    {
        return $this->requiresApprovalWhenTrait($modifications) && ($modifications[static::$valid_from_column] ?? $modifications[static::$valid_to_column] ?? false);
    }
}
