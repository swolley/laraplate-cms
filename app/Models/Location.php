<?php

namespace Modules\Cms\Models;

use Illuminate\Support\Str;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Core\Cache\Searchable;
use Modules\Core\Helpers\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Helpers\HasValidations;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Modules\Cms\Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
 * @mixin IdeHelperLocation
 */
class Location extends Model
{
    use HasFactory, HasSlug, HasPath, HasValidations, Searchable, HasSpatial, SoftDeletes {
        prepareElasticDocument as prepareElasticDocumentTrait;
        getRules as protected getRulesTrait;
    }

    protected array $textOnlyFields = ['name', 'address', 'city', 'province', 'country', 'postcode', 'zone'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'province',
        'country',
        'postcode',
        'geolocation',
        'zone',
        'slug',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    #[\Override]
    protected function casts()
    {
        return [
            'geolocation' => Point::class,
        ];
    }

    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }

    protected function getLatitudeAttribute(): ?float
    {
        return $this->geolocation?->getLatitude();
    }

    protected function getLongitudeAttribute(): ?float
    {
        return $this->geolocation?->getLongitude();
    }

    protected function setLatitudeAttribute(float $value): void
    {
        $longitude = $this->geolocation?->longitude ?? 0.0;
        $this->geolocation = new Point($value, $longitude);
    }

    protected function setLongitudeAttribute(float $value): void
    {
        $latitude = $this->geolocation?->latitude ?? 0.0;
        $this->geolocation = new Point($latitude, $value);
    }

    public function prepareElasticDocument(): array
    {
        $document = $this->prepareElasticDocumentTrait();
        $document['id'] = $this->id;
        $document['geocode'] = [
            'lat' => (float)$this->latitude,
            'lon' => (float)$this->longitude
        ];
        unset($document['latitude'], $document['longitude']);

        return $document;
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        // $rules[static::DEFAULT_RULE] = array_merge($rules[static::DEFAULT_RULE], [
        //     'latitude' => ['sometimes', 'numeric', 'min:-90', 'max:90'],
        //     'longitude' => ['sometimes', 'numeric', 'min:-180', 'max:180'],
        // ]);
        $rules['create'] = array_merge($rules['create'], [
            'name' => ['required', 'string', 'max:255', 'unique:locations,name'],
            'country' => ['required', 'string', 'max:255'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => ['sometimes', 'string', 'max:255', 'unique:locations,name,' . $this->id],
            'country' => ['sometimes', 'string', 'max:255'],
        ]);
        return $rules;
    }

    // public function getPathPrefix(): string
    // {
    //     return '';
    // }

    #[\Override]
    public function getPath(): ?string
    {
        return Str::slug($this->country);
    }

    /**
     * The contents that belong to the location.
     * @return BelongsToMany<Content>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class);
    }
}
