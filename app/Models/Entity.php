<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Override;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Core\Cache\HasCache;
use Modules\Cms\Casts\EntityType;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Helpers\HasValidations;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Locking\Traits\HasLocks;
use Modules\Core\Overrides\ComposhipsModel;
use Modules\Cms\Database\Factories\EntityFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperEntity
 */
final class Entity extends ComposhipsModel
{
    use HasCache, HasFactory, HasLocks, HasPath, HasSlug, HasValidations {
        getRules as protected getRulesTrait;
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'is_active',
        'type',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * The presets that belong to the entity.
     *
     * @return HasMany<Preset>
     */
    public function presets(): HasMany
    {
        return $this->hasMany(Preset::class);
    }

    /**
     * The contents that belong to the entity.
     *
     * @return HasMany<Content>
     */
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    /**
     * The categories that belong to the entity.
     *
     * @return HasMany<Category>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], [
            'is_active' => 'boolean',
            'slug' => 'nullable|string|max:255',
            'type' => ['required', EntityType::validationRule()],
        ]);
        $rules['create'] = array_merge($rules['create'], [
            'name' => ['required', 'string', 'max:255', 'unique:entities,name'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => ['sometimes', 'string', 'max:255', 'unique:entities,name,' . $this->id],
        ]);

        return $rules;
    }

    #[Override]
    public function getPath(): ?string
    {
        return null;
    }

    protected static function newFactory(): EntityFactory
    {
        return EntityFactory::new();
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        // self::addGlobalScope('api', function (Builder $builder) {
        //     if (request()?->is('api/*')) {
        //         $builder->where('is_active', true);
        //     }
        // });
        self::addGlobalScope('active', function (Builder $builder): void {
            $builder->where('is_active', true);
        });
    }

    #[Override]
    protected static function booted(): void
    {
        self::saved(function (Entity $entity): void {
            Cache::forget(new Preset()->getCacheKey());
            Content::resolveChildTypes();
        });

        self::deleted(function (Entity $entity): void {
            Cache::forget(new Preset()->getCacheKey());
            Content::resolveChildTypes();
        });
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
            'type' => EntityType::class,
        ];
    }
}
