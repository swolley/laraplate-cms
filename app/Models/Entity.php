<?php

namespace Modules\Cms\Models;

use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Core\Cache\HasCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Helpers\HasValidations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Cms\Database\Factories\EntityFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @mixin IdeHelperEntity
 */
class Entity extends Model
{
    use HasFactory, SoftDeletes, HasCache, HasSlug, HasPath, HasValidations {
        getRules as protected getRulesTrait;
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): EntityFactory
    {
        return EntityFactory::new();
    }

    #[\Override]
    protected static function boot()
    {
        parent::boot();

        self::addGlobalScope('api', function (Builder $builder) {
            if (request()?->is('api/*')) {
                $builder->where('is_active', true);
            }
        });
    }

    #[\Override]
    protected static function booted(): void
    {
        static::saved(function (Entity $entity) {
            Cache::forget(new Preset()->getCacheKey());
            Content::resolveChildTypes();
        });

        static::forceDeleted(function (Entity $entity) {
            Cache::forget(new Preset()->getCacheKey());
            Content::resolveChildTypes();
        });
    }

    /**
     * The presets that belong to the entity.
     * @return HasMany<Preset>
     */
    public function presets(): HasMany
    {
        return $this->hasMany(Preset::class);
    }

    /**
     * The contents that belong to the entity.
     * @return HasManyThrough<Content>
     */
    public function contents(): HasManyThrough
    {
        return $this->hasManyThrough(Content::class, Preset::class);
    }

    /**
     * The categories that belong to the entity.
     * @return HasMany<Category>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules[static::DEFAULT_RULE] = array_merge($rules[static::DEFAULT_RULE], [
            'is_active' => 'boolean',
            'slug' => 'nullable|string|max:255',
        ]);
        $rules['create'] = array_merge($rules['create'], [
            'name' => ['required', 'string', 'max:255', 'unique:entities,name'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => ['sometimes', 'string', 'max:255', 'unique:entities,name,' . $this->id],
        ]);
        return $rules;
    }

    #[\Override]
    public function getPath(): ?string
    {
        return null;
    }
}
