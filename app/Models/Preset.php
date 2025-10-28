<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Cms\Models\Pivot\Fieldable;
use Modules\Cms\Models\Pivot\Presettable;
use Modules\Core\Cache\HasCache;
use Modules\Core\Helpers\HasApprovals;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Helpers\HasVersions;
use Modules\Core\Helpers\SoftDeletes;
use Override;

// use Modules\Cms\Database\Factories\ModelTypeFactory;

/**
 * @mixin IdeHelperPreset
 */
final class Preset extends Model
{
    use HasApprovals;
    use HasCache;
    use HasFactory;
    use HasValidations;
    use HasVersions;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'entity_id',
        'name',
        'is_active',
        'template_id',
    ];

    protected $hidden = [
        'entity_id',
        'template_id',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * @return BelongsTo<Template>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * @return BelongsTo<Entity>
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * @return HasManyThrough<Content>
     */
    public function contents(): HasManyThrough
    {
        return $this->hasManyThrough(Content::class, Presettable::class);
    }

    /**
     * @return BelongsToMany<Field>
     */
    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(Field::class, 'fieldables')->using(Fieldable::class)->withTimestamps()->withPivot(['id', 'order_column', 'is_required', 'default']);
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], [
            'is_active' => 'boolean',
            'template_id' => ['sometimes', 'exists:templates,id'],
            'entity_id' => ['required', 'exists:entities,id'],
        ]);
        $rules['create'] = array_merge($rules['create'], [
            'name' => ['required', 'string', 'max:255'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        return $rules;
    }

    // protected static function newFactory(): ModelTypeFactory
    // {
    //     // return ModelTypeFactory::new();
    // }

    // protected static function boot()
    // {
    //     parent::boot();

    //     self::addGlobalScope('api', function (Builder $builder) {
    //         if (request()?->is('api/*')) {
    //             $builder->where('is_active', true);
    //         }
    //     });
    // }

    #[Override]
    protected function casts(): array
    {
        return [
            'template_id' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
