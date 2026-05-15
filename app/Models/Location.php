<?php

declare(strict_types=1);

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Modules\CMS\Contracts\Taggable;
use Modules\CMS\Database\Factories\LocationFactory;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Helpers\HasTags;
use Modules\CMS\Models\Pivot\Locatable;
use Modules\CMS\Observers\LocationObserver;
use Modules\Core\Helpers\HasPath;
use Modules\Core\Helpers\HasPlace;
use Modules\Core\Helpers\HasSlug;
use Modules\Core\Overrides\Model;
use Modules\Core\Search\Schema\FieldDefinition;
use Modules\Core\Search\Schema\FieldType;
use Modules\Core\Search\Schema\IndexType;
use Modules\Core\Search\Traits\Searchable;
use Override;

/**
 * @method static whereDistance(Point $point, float $distance)
 * @method static orderByDistance(Point $point, string $direction = 'asc')
 * @method static whereDistanceSphere(Point $point, float $distance)
 * @method static orderByDistanceSphere(Point $point, string $direction = 'asc')
 * @method static whereWithin(Polygon $polygon)
 * @method static whereNotWithin(Polygon $polygon)
 * @method static whereContains(Polygon $polygon)
 * @method static whereNotContains(Polygon $polygon)
 * @method static whereEquals(Point $point)
 * @mixin \Eloquent
 * @mixin IdeHelperLocation
 */
#[ObservedBy(LocationObserver::class)]
final class Location extends Model implements Taggable
{
    use HasPath;
    use HasPlace;
    use HasSlug;
    use HasSpatial;
    use HasTags;
    use Searchable {
        toSearchableArray as private toSearchableArrayTrait;
        getSearchMapping as private getSearchMappingTrait;
    }

    #[Override]
    protected $table = CMSTables::Locations->value;

    /**
     * The attributes that are mass assignable.
     */
    #[Override]
    protected $fillable = [
        'name',
        'place_id',
        'address',
        'city',
        'province',
        'country',
        'postcode',
        'geolocation',
        'zone',
        'slug',
    ];

    #[Override]
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Typesense mapping to avoid empty schema errors.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSearchMapping(): array
    {
        $schema = $this->getSchemaDefinition();
        $schema->addField(new FieldDefinition('id', FieldType::Keyword, [IndexType::Searchable]));
        $schema->addField(new FieldDefinition('entity', FieldType::Keyword, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable]));
        $schema->addField(new FieldDefinition('connection', FieldType::Keyword, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable]));
        $schema->addField(new FieldDefinition(self::$indexedAtField, FieldType::Date, [IndexType::Searchable, IndexType::Filterable, IndexType::Sortable]));
        $schema->addField(new FieldDefinition('name', FieldType::Text, [IndexType::Searchable, IndexType::Filterable]));
        $schema->addField(new FieldDefinition('slug', FieldType::Keyword, [IndexType::Searchable]));
        $schema->addField(new FieldDefinition('address', FieldType::Text, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable]));
        $schema->addField(new FieldDefinition('city', FieldType::Keyword, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable]));
        $schema->addField(new FieldDefinition('province', FieldType::Keyword, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable]));
        $schema->addField(new FieldDefinition('country', FieldType::Keyword, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable]));
        $schema->addField(new FieldDefinition('postcode', FieldType::Keyword, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable]));
        $schema->addField(new FieldDefinition('zone', FieldType::Keyword, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable]));
        $schema->addField(new FieldDefinition('geocode', FieldType::Geocode, [IndexType::Searchable, IndexType::Filterable, IndexType::Facetable]));

        return $this->getSearchMappingTrait($schema);
    }

    public function toSearchableArray(): array
    {
        $document = array_merge($this->toSearchableArrayTrait(), Arr::except($this->toArray(), ['id', 'latitude', 'longitude']));
        $document['id'] = (string) $this->getKey();

        if (! empty($this->attributes['place_id'] ?? null)) {
            $place = $this->resolvePlace();

            if ($place instanceof \Modules\Core\Models\Place) {
                return array_merge($document, $place->searchDocumentGeographyFields());
            }
        }

        $document['geocode'] = [(float) $this->latitude, (float) $this->longitude];

        return $document;
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        // $rules[Model::DEFAULT_RULE] = array_merge($rules[Model::DEFAULT_RULE], [
        //     'latitude' => ['sometimes', 'numeric', 'min:-90', 'max:90'],
        //     'longitude' => ['sometimes', 'numeric', 'min:-180', 'max:180'],
        // ]);
        $rules['create'] = array_merge($rules['create'], [
            'name' => ['required', 'string', 'max:255', 'unique:'.CMSTables::Locations->value.',name'],
            'country' => ['required', 'string', 'max:255'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => ['sometimes', 'string', 'max:255', 'unique:'.CMSTables::Locations->value.',name,' . $this->id],
            'country' => ['sometimes', 'string', 'max:255'],
        ]);

        return $rules;
    }

    // public function getPathPrefix(): string
    // {
    //     return '';
    // }

    #[Override]
    public function getPath(): string
    {
        if (! empty($this->attributes['place_id'] ?? null)) {
            $place = $this->resolvePlace();

            if ($place instanceof \Modules\Core\Models\Place) {
                return $place->countryPathSegment();
            }
        }

        return Str::slug((string) ($this->getAttribute('country') ?? ''));
    }

    /**
     * The contents that belong to the location.
     *
     * @return BelongsToMany<Content, Location, Locatable, 'pivot'>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'locatables')
            ->using(Locatable::class)
            ->withTimestamps();
    }

    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }

    // region Attributes

    /**
     * Used when {@see HasPlace} holds a point only until {@see place_id} exists: decimals come from {@see geolocation}.
     */
    protected function getLatitudeAttribute(): ?float
    {
        return $this->geolocation?->latitude;
    }

    protected function getLongitudeAttribute(): ?float
    {
        return $this->geolocation?->longitude;
    }

    protected function setLatitudeAttribute(float $value): void
    {
        $current = $this->geolocation;
        $longitude = $current instanceof Point ? $current->longitude : 0.0;
        $this->geolocation = new Point($value, $longitude);
    }

    protected function setLongitudeAttribute(float $value): void
    {
        $current = $this->geolocation;
        $latitude = $current instanceof Point ? $current->latitude : 0.0;
        $this->geolocation = new Point($latitude, $value);
    }
}
