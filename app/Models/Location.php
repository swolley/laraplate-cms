<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Modules\Cms\Database\Factories\LocationFactory;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Cms\Helpers\HasTags;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Helpers\SoftDeletes;
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
 * @mixin IdeHelperLocation
 */
final class Location extends Model
{
    use HasFactory, HasPath, HasSlug, HasSpatial, HasTags, HasValidations, Searchable, SoftDeletes {
        toSearchableArray as toSearchableArrayTrait;
        getRules as protected getRulesTrait;
    }

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

    private array $textOnlyFields = ['name', 'address', 'city', 'province', 'country', 'postcode', 'zone'];

    public function toSearchableArray(): array
    {
        $document = array_merge($this->toSearchableArrayTrait(), Arr::except($this->toArray(), ['id', 'latitude', 'longitude']));
        $document['geocode'] = [
            'lat' => (float) $this->latitude,
            'lon' => (float) $this->longitude,
        ];

        return $document;
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        // $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], [
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

    #[Override]
    public function getPath(): ?string
    {
        return Str::slug($this->country);
    }

    /**
     * The contents that belong to the location.
     *
     * @return BelongsToMany<Content>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class);
    }

    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'geolocation' => Point::class,
        ];
    }

    // region Attributes

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
}
