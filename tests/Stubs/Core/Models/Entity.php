<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Cms\Database\Factories\EntityFactory as CmsEntityFactory;
use Modules\Core\Cache\HasCache;
use Modules\Core\Helpers\HasActivation;
use Modules\Core\Helpers\HasPath;
use Modules\Core\Helpers\HasSlug;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Locking\Traits\HasLocks;
use Override;

/**
 * Test stub: minimal Core entity base for CMS module tests without loading the full Core package.
 *
 * @property int|string $id
 * @property string $name
 * @property string $slug
 */
abstract class Entity extends Model
{
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

    #[Override]
    final protected $table = 'entities';

    #[Override]
    final protected $fillable = [
        'name',
        'slug',
        'type',
    ];

    #[Override]
    final protected $hidden = [
        'created_at',
        'updated_at',
        'type',
    ];

    /**
     * @return HasMany<\Modules\Cms\Models\Preset>
     */
    final public function presets(): HasMany
    {
        return $this->hasMany(\Modules\Cms\Models\Preset::class);
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], [
            'is_active' => 'boolean',
            'slug' => 'sometimes|nullable|string|max:255',
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

    protected static function newFactory(): CmsEntityFactory
    {
        return CmsEntityFactory::new();
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
        ]);
    }
}
