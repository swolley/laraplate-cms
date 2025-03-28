<?php

namespace Modules\Cms\Models;

use Illuminate\Support\Str;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Core\Cache\Searchable;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Helpers\HasValidations;
use Modules\Cms\Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperLocation
 */
class Location extends Model
{
    use HasFactory, HasSlug, HasPath, HasValidations, Searchable {
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
        'latitude',
        'longitude',
        'zone',
        'slug',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    #[\Override]
    protected function casts()
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
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
        $rules[static::DEFAULT_RULE] = array_merge($rules[static::DEFAULT_RULE], [
            'latitude' => ['sometimes', 'numeric', 'min:-90', 'max:90'],
            'longitude' => ['sometimes', 'numeric', 'min:-180', 'max:180'],
        ]);
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
}
