<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Database\Factories\EntityFactory;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Cms\Models\Pivot\Presettable;
use Modules\Core\Cache\HasCache;
use Modules\Core\Helpers\HasActivation;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Locking\Traits\HasLocks;
use Override;

/**
 * @mixin IdeHelperEntity
 */
final class Entity extends Model
{
    // region Traits
    use HasActivation {
        HasActivation::casts as private activationCasts;
    }
    use HasCache;
    use HasFactory;
    use HasLocks;
    use HasPath;
    use HasSlug;
    use HasValidations {
        getRules as private getRulesTrait;
    }
    // endregion

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
        'type',
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
     * @return HasManyThrough<Content>
     */
    public function contents(): HasManyThrough
    {
        return $this->hasManyThrough(Content::class, Presettable::class);
    }

    /**
     * The categories that belong to the entity.
     *
     * @return HasManyThrough<Category>
     */
    public function categories(): HasManyThrough
    {
        return $this->hasManyThrough(Category::class, Presettable::class);
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], [
            'is_active' => 'boolean',
            'slug' => 'sometimes|nullable|string|max:255',
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
    protected static function booted(): void
    {
        self::addGlobalScope('active', static function (Builder $builder): void {
            $builder->active();
        });
    }

    protected function casts(): array
    {
        return array_merge($this->activationCasts(), [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
            'type' => EntityType::class,
        ]);
    }
}
