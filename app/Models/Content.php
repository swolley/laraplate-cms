<?php

declare(strict_types=1);

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Casts\ReadingStatistics;
use Modules\CMS\Contracts\Taggable;
use Modules\CMS\Database\Factories\ContentFactory;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Helpers\HasMultimedia;
use Modules\CMS\Helpers\HasTags;
use Modules\CMS\Models\Pivot\Categorizable;
use Modules\CMS\Models\Pivot\Contributable;
use Modules\CMS\Models\Pivot\Locatable;
use Modules\CMS\Models\Pivot\Relatable;
use Modules\CMS\Models\Translations\ContentTranslation;
use Modules\CMS\Observers\ContentObserver;
use Modules\Core\Contracts\IDynamicEntityTypable;
use Modules\Core\Enums\CoreTables;
use Modules\Core\Helpers\LocaleContext;
use Modules\Core\Locking\Traits\HasLocks;
use Modules\Core\Locking\Traits\HasOptimisticLocking;
use Modules\Core\Models\Concerns\HasApprovals;
use Modules\Core\Models\Concerns\HasPath;
use Modules\Core\Models\Concerns\HasTranslatedDynamicContents;
use Modules\Core\Models\Concerns\HasValidity;
use Modules\Core\Models\Concerns\SortableTrait;
use Modules\Core\Models\RecordOrigin;
use Modules\Core\Overrides\Model;
use Modules\Core\Search\Schema\FieldDefinition;
use Modules\Core\Search\Schema\FieldType;
use Modules\Core\Search\Schema\IndexType;
use Modules\Core\Search\Traits\Searchable;
use Override;
use Spatie\EloquentSortable\Sortable;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int|string $id
 * @phpstan-use HasMultimedia<Content>
 * @phpstan-use HasTranslatedDynamicContents<Content>
 * @phpstan-use HasValidity<Content>
 * @phpstan-use Searchable<Content>
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin \Eloquent
 * @mixin IdeHelperContent
 */
#[ObservedBy(ContentObserver::class)]
final class Content extends Model implements HasMedia, Sortable, Taggable
{
    // region Traits
    use HasApprovals {
        HasApprovals::toArray as private approvalsToArray;
        HasApprovals::requiresApprovalWhen as private requiresApprovalWhenTrait;
    }
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
    use HasValidity;
    use Searchable {
        Searchable::toSearchableArray as private toSearchableArrayTrait;
        Searchable::getSearchMapping as private getSearchMappingTrait;
    }
    use SortableTrait {
        SortableTrait::scopeOrdered as private scopePriorityOrdered;
    }

    /**
     * @var array<int|string, class-string<static>>
     */
    public static array $childTypes = [];

    /**
     * @var string
     */
    #[Override]
    protected $table = CMSTables::Contents->value;

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
    #[Override]
    protected $appends = [
        'statistics',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [];

    /**
     * @var list<string>
     */
    protected array $embed = ['title', 'textual_only'];

    /**
     * @var array<string, mixed>
     */
    protected array $sortable = [
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
            $entity_id = self::fetchAvailableEntities(EntityType::Contents)->firstWhere('id', $entity)?->id;
        } elseif (is_string($entity)) {
            $entity_id = self::fetchAvailableEntities(EntityType::Contents)->firstWhere('slug', $entity)?->id;
        } else {
            $entity_id = array_key_exists($entity->id, self::$childTypes) ? $entity->id : null;
        }

        if (in_array($entity_id, ['', '0', 0, null], true)) {
            throw new InvalidArgumentException('Invalid entity: ' . json_encode($entity));
        }

        $normalized_entity_id = is_int($entity_id) ? $entity_id : (int) $entity_id;

        if ($entity instanceof Entity && $entity->type !== EntityType::Contents) {
            throw new InvalidArgumentException('Invalid entity type for content: ' . $entity->type->toScalar());
        }

        $presettable = self::fetchAvailablePresettables(EntityType::Contents)->firstWhere('entity_id', $normalized_entity_id);

        throw_unless($presettable, InvalidArgumentException::class, 'No presettable found for entity: ' . $entity);

        $child_class = self::$childTypes[$normalized_entity_id]
            ?? self::$childTypes[(string) $normalized_entity_id]
            ?? null;

        if ($child_class === null) {
            throw new InvalidArgumentException('No content class registered for entity id: ' . $normalized_entity_id);
        }

        return new $child_class([
            'presettable_id' => $presettable->id,
            'entity_id' => $normalized_entity_id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
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
        return $this->belongsToMany(Category::class, CMSTables::Categorizables->value, 'content_id', 'taxonomy_id')
            ->using(Categorizable::class)
            ->withTimestamps();
    }

    /**
     * The locations that belong to the content.
     *
     * @return BelongsToMany<Location, $this, Locatable, 'pivot'>
     */
    public function locations(): BelongsToMany
    {
        $relation = $this->belongsToMany(Location::class, CMSTables::Locatables->value)->using(Locatable::class)->withTimestamps();
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
        return $this->belongsToMany(Contributor::class, CMSTables::Contributables->value)->using(Contributable::class)->withTimestamps();
    }

    /**
     * Comments on this content (approved/persisted rows only).
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<ContentRating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(ContentRating::class);
    }

    /**
     * The external references (bibliography) cited by this content.
     *
     * @return HasMany<ContentReference, $this>
     */
    public function references(): HasMany
    {
        return $this->hasMany(ContentReference::class);
    }

    /**
     * The provenance of this content (external origin source or manual attribution).
     *
     * @return MorphOne<RecordOrigin, $this>
     */
    public function origin(): MorphOne
    {
        return $this->morphOne(RecordOrigin::class, 'referable');
    }

    /**
     * The related contents that belong to the content.
     *
     * @return BelongsToMany<Content, $this, Relatable, 'pivot'>
     */
    public function related(?bool $withInverse = false): BelongsToMany
    {
        $relation = $this->belongsToMany(
            self::class,
            CMSTables::Relatables->value,
            'content_id',
            'related_content_id',
        )->using(Relatable::class)->withTimestamps();

        if ($withInverse === true) {
            $relation->orWhere(
                fn (Builder $query) => $query->where(
                    DB::raw(CMSTables::Relatables->value . '.related_content_id'),
                    $this->id,
                ),
            );
        }

        return $relation;
    }

    /**
     * Get the relations to eager load when indexing the model.
     *
     * @return list<string>
     */
    public function toSearchableWith(): array
    {
        return [
            'contributors',
            'categories',
            'tags',
            'locations',
            'translations',
            'presettable.entity',
            'presettable.preset',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        // TODO: transform splitted values into objects? (contributors, categories, tags, locations)
        $document = $this->toSearchableArrayTrait();
        // $document['entity'] = $this->entity->name;
        // $document['entity_id'] = $this->entity->id;
        $preset = $this->preset;
        $document['preset'] = $preset !== null ? $preset->name : '';
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
        $default_locale = is_string(config('app.locale')) ? config('app.locale') : 'en';
        $available_locales = LocaleContext::getAvailable();

        // Add base fields with default translation (for compatibility/fallback)
        $default_translation = $this->findContentTranslation($default_locale);

        if ($default_translation instanceof ContentTranslation) {
            $document['slug'] = $default_translation->slug;
            $document['title'] = $default_translation->title;

            // Add default components
            if ($default_translation->components !== null) {
                foreach ($default_translation->components as $field => $value) {
                    $document[$field] = gettype($value) === 'string' ? Str::replaceMatches('/\\n|\\r|\\t/', '', $value) : $value;
                }
            }
        }

        // Add fields for each locale (title_locale, slug_locale, components_locale)
        foreach ($available_locales as $locale) {
            $translation = $this->findContentTranslation($locale);

            if ($translation instanceof ContentTranslation) {
                $document['title_' . $locale] = $translation->title;
                $document['slug_' . $locale] = $translation->slug;
            }
        }

        return $document;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSearchMapping(): array
    {
        $schema = $this->getSchemaDefinition();
        $schema->addField(new FieldDefinition('entity', FieldType::Keyword, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable]));
        $schema->addField(new FieldDefinition('preset', FieldType::Keyword, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable]));
        $schema->addField(new FieldDefinition('contributors', FieldType::Array, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable], [
            'relation' => 'contributors',
            'properties' => [
                'name' => FieldType::Text,
                'id' => ['type' => FieldType::Integer, 'filterable' => true],
                'slug' => ['type' => FieldType::Keyword, 'filterable' => true],
                'path' => ['type' => FieldType::Keyword, 'filterable' => true],
            ],
        ]));
        $schema->addField(new FieldDefinition('categories', FieldType::Array, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable], [
            'relation' => 'categories',
            'properties' => [
                'name' => FieldType::Text,
                'id' => ['type' => FieldType::Integer, 'filterable' => true],
                'slug' => ['type' => FieldType::Keyword, 'filterable' => true],
                'path' => ['type' => FieldType::Keyword, 'filterable' => true],
            ],
        ]));
        $schema->addField(new FieldDefinition('tags', FieldType::Array, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable], [
            'relation' => 'tags',
            'properties' => [
                'name' => FieldType::Keyword,
                'id' => ['type' => FieldType::Integer, 'filterable' => true],
                'slug' => ['type' => FieldType::Keyword, 'filterable' => true],
                'path' => ['type' => FieldType::Keyword, 'filterable' => true],
            ],
        ]));
        $schema->addField(new FieldDefinition('locations', FieldType::Array, [IndexType::Searchable, IndexType::Filterable], [
            'relation' => 'locations',
            'properties' => [
                'name' => FieldType::Text,
                'id' => ['type' => FieldType::Integer, 'filterable' => true],
                'slug' => ['type' => FieldType::Keyword, 'filterable' => true],
                'path' => FieldType::Keyword,
                'address' => FieldType::Text,
                'city' => ['type' => FieldType::Keyword, 'filterable' => true],
                'province' => ['type' => FieldType::Keyword, 'filterable' => true],
                'country' => ['type' => FieldType::Keyword, 'filterable' => true],
                'postcode' => ['type' => FieldType::Keyword, 'filterable' => true],
                'zone' => ['type' => FieldType::Keyword, 'filterable' => true],
            ],
        ]));
        $schema->addField(new FieldDefinition('type', FieldType::Keyword, [IndexType::Searchable, IndexType::Filterable]));
        $schema->addField(new FieldDefinition('valid_from', FieldType::Date, [IndexType::Searchable, IndexType::Filterable, IndexType::Sortable]));
        $schema->addField(new FieldDefinition('valid_to', FieldType::Date, [IndexType::Searchable, IndexType::Filterable, IndexType::Sortable]));
        $schema->addField(new FieldDefinition('is_deleted', FieldType::Boolean, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable]));
        $schema->addField(new FieldDefinition('embedding', FieldType::Vector, [IndexType::Searchable, IndexType::Vector]));

        // Base fields with default translation (for compatibility/fallback)
        $schema->addField(new FieldDefinition('slug', FieldType::Keyword, [IndexType::Searchable]));
        $schema->addField(new FieldDefinition('title', FieldType::Text, [IndexType::Searchable, IndexType::Filterable]));

        // Add fields for each locale
        $available_locales = LocaleContext::getAvailable();
        $default_translation = $this->findContentTranslation(
            is_string(config('app.locale')) ? config('app.locale') : 'en',
        );

        // Get component fields from default translation
        $component_fields = [];

        if ($default_translation instanceof ContentTranslation && $default_translation->components !== null) {
            $component_fields = array_keys($default_translation->components);
        }

        foreach ($available_locales as $locale) {
            // Add title and slug for each locale
            $schema->addField(new FieldDefinition('title_' . $locale, FieldType::Text, [IndexType::Searchable, IndexType::Filterable]));
            $schema->addField(new FieldDefinition('slug_' . $locale, FieldType::Keyword, [IndexType::Searchable]));
        }

        // Add base component fields (from default translation)
        foreach ($component_fields as $field) {
            $schema->addField(new FieldDefinition($field, FieldType::Text, [IndexType::Searchable]));
        }

        return $this->getSearchMappingTrait($schema);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        $rules = parent::getRules();
        $fields = $this->getRulesTranslatedDynamicContents();
        $rules[Model::DEFAULT_RULE] = array_merge($rules[Model::DEFAULT_RULE], $fields);
        $rules['create'] = array_merge($rules['create'], [
            // 'title' => 'required|string|max:255', // Validated in translation
            // 'slug' => 'sometimes|nullable|string|max:255', // Validated in translation
            'entity_id' => 'required|exists:' . CoreTables::Entities->value . ',id',
            'presettable_id' => 'required|exists:' . CoreTables::Presettables->value . ',id',
            'translations' => 'sometimes|array',
            'translations.*.locale' => 'required|string|max:10',
            'translations.*.title' => 'required|string|max:255',
            'translations.*.slug' => 'sometimes|nullable|string|max:255',
            'translations.*.components' => 'sometimes|array',
            'translations.*.ai_assistance' => 'sometimes|string|in:none,generated,translated,edited,summarized',
        ]);
        $rules['update'] = array_merge($rules['update'], [
            // 'title' => 'sometimes|required|string|max:255', // Validated in translation
            // 'slug' => 'sometimes|nullable|string|max:255', // Validated in translation
            'entity_id' => 'sometimes|required|exists:' . CoreTables::Entities->value . ',id',
            'presettable_id' => 'sometimes|required|exists:' . CoreTables::Presettables->value . ',id',
            'translations' => 'sometimes|array',
            'translations.*.locale' => 'required|string|max:10',
            'translations.*.title' => 'sometimes|required|string|max:255',
            'translations.*.slug' => 'sometimes|nullable|string|max:255',
            'translations.*.components' => 'sometimes|array',
            'translations.*.ai_assistance' => 'sometimes|string|in:none,generated,translated,edited,summarized',
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
        $entity = self::fetchAvailableEntities(EntityType::Contents)->firstWhere('name', $entity_name);

        if (! $entity) {
            return;
        }

        // Only set if not already set
        $this->entity_id = is_int($entity->id) ? $entity->id : (int) $entity->id;
        $this->setRelation('entity', $entity);

        // Find first available preset for this entity
        $presettable = self::fetchAvailablePresettables(EntityType::Contents)
            ->firstWhere('entity_id', $entity->id);

        if (! $presettable) {
            return;
        }

        $this->presettable_id = $presettable->id;
        $this->setRelation('presettable', $presettable);
    }

    protected static function getEntityType(): IDynamicEntityTypable
    {
        return EntityType::Contents;
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
     *
     * @return Attribute<array<string, mixed>, array<string, mixed>>
     */
    protected function components(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                // components is a translatable field, get it from HasTranslations
                $raw_components = $this->translationsGet('components');

                // If we got components from translation, merge with defaults using HasDynamicContents
                if ($raw_components !== null) {
                    return $this->mergeComponentsValues(is_array($raw_components) ? $raw_components : []);
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

    /**
     * @return list<string>
     */
    protected function slugPlaceholders(): array
    {
        // Use title from translation
        return array_values([...array_map(fn (string $field): string => '{' . $field . '}', $this->dynamicSlugFields()), '{title}']);
    }

    /**
     * @param  array<string, mixed>  $modifications
     */
    protected function requiresApprovalWhen(array $modifications): bool
    {
        return $this->requiresApprovalWhenTrait($modifications) && ($modifications[self::$valid_from_column] ?? $modifications[self::$valid_to_column] ?? false);
    }

    /**
     * @return Attribute<ReadingStatistics, never>
     */
    protected function statistics(): Attribute
    {
        return Attribute::make(
            get: function (): ReadingStatistics {
                $raw = $this->getAttribute('content');
                $blocks = match (true) {
                    is_array($raw) => $raw['blocks'] ?? [],
                    is_object($raw) => $raw->blocks ?? [],
                    default => [],
                };

                if (! is_iterable($blocks)) {
                    $blocks = [];
                }

                return ReadingStatistics::fromBlocks($blocks);
            },
        );
    }

    private function findContentTranslation(string $locale): ?ContentTranslation
    {
        $translation = $this->translations->firstWhere('locale', $locale);

        return $translation instanceof ContentTranslation ? $translation : null;
    }
}
