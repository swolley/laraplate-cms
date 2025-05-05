<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Override;
use Illuminate\Validation\Rule;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Casts\ObjectCast;
use Modules\Core\Helpers\HasVersions;
use Modules\Core\Helpers\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Modules\Cms\Models\Pivot\Fieldable;
use Modules\Core\Helpers\HasValidations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read object $options
 * @mixin IdeHelperField
 */
final class Field extends Model
{
    use HasFactory, HasValidations, HasVersions, SoftDeletes {
        getRules as protected getRulesTrait;
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'type',
        'options',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    #[Override]
    public function __get($key)
    {
        if (property_exists($this, 'pivot') && $this->pivot !== null && isset($this->pivot->{$key})) {
            return $this->pivot->{$key};
        }

        return parent::__get($key);
    }

    #[Override]
    public function __set($key, $value): void
    {
        // if (array_key_exists($key, $this->attributes)) {
        //     parent::__set($key, $value);
        //     return;
        // }
        if (property_exists($this, 'pivot') && $this->pivot !== null && array_key_exists($key, $this->pivot->getAttributes())) {
            // @phpstan-ignore assign.propertyReadOnly
            data_set($this->pivot, $key, $value);

            return;
        }
        parent::__set($key, $value);
    }

    public function presets(): BelongsToMany
    {
        return $this->belongsToMany(Preset::class, 'fieldables')->using(Fieldable::class)->withTimestamps()->withPivot(['order_column', 'is_required', 'default']);
    }

    #[Override]
    public function toArray(): array
    {
        $field = parent::toArray();

        if (isset($field['pivot'])) {
            $pivot = $field['pivot'];
            unset($field['pivot']);
            $field = array_merge($field, $pivot);
        } elseif (property_exists($this, 'pivot') && $this->pivot !== null) {
            $field = array_merge($field, $this->pivot->toArray());
        }

        return $field;
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], [
            'is_active' => 'boolean',
            'type' => ['required', 'string', Rule::enum(FieldType::class)],
        ]);
        $rules['create'] = array_merge($rules['create'], [
            'name' => ['required', 'string', 'max:255', 'unique:fields,name'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => ['sometimes', 'string', 'max:255', 'unique:fields,name,' . $this->id],
        ]);

        return $rules;
    }

    protected static function boot(): void
    {
        parent::boot();

        // self::addGlobalScope('api', function (Builder $builder) {
        //     if (request()?->is('api/*')) {
        //         $builder->where('is_active', true);
        //     }
        // });

        self::updating(function (Field $model): void {
            if (property_exists($model, 'pivot') && $model->pivot && $model->pivot->isDirty()) {
                $model->pivot->save();
            }
        });
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'options' => ObjectCast::class,
            'is_active' => 'boolean',
            'type' => FieldType::class,
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
