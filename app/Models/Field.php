<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\Rule;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Casts\ObjectCast;
use Modules\Cms\Models\Pivot\Fieldable;
use Modules\Cms\Observers\FieldObserver;
use Modules\Core\Helpers\HasActivation;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Helpers\HasVersions;
use Modules\Core\Helpers\SoftDeletes;
use Override;

/**
 * @property-read object $options
 * @mixin IdeHelperField
 */
#[ObservedBy(FieldObserver::class)]
final class Field extends Model
{
    // region Traits
    use HasActivation {
        HasActivation::casts as private activationCasts;
    }
    use HasFactory;
    use HasValidations {
        getRules as private getRulesTrait;
    }
    use HasVersions;
    use SoftDeletes;
    // endregion

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'type',
        'options',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function getAttribute($key): mixed
    {
        if (property_exists($this, 'pivot') && $this->pivot !== null && isset($this->pivot->{$key})) {
            return $this->pivot->{$key};
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value): void
    {
        if (property_exists($this, 'pivot') && $this->pivot !== null && array_key_exists($key, $this->pivot->getAttributes())) {
            // @phpstan-ignore assign.propertyReadOnly
            data_set($this->pivot, $key, $value);

            return;
        }

        parent::setAttribute($key, $value);
    }

    /**
     * @return BelongsToMany<Preset>
     */
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

    protected function casts(): array
    {
        return array_merge($this->activationCasts(), [
            'options' => ObjectCast::class,
            'type' => FieldType::class,
            'is_translatable' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ]);
    }
}
