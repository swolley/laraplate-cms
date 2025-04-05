<?php

namespace Modules\Cms\Models;

use ArrayAccess;
use Illuminate\Validation\Rule;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Illuminate\Support\Collection;
use Modules\Core\Helpers\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Helpers\HasValidations;
use Illuminate\Database\Eloquent\Builder;
use Spatie\EloquentSortable\SortableTrait;
use Modules\Cms\Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection as DbCollection;

/**
 * @mixin IdeHelperTag
 */
class Tag extends Model implements Sortable
{
    use HasValidations, HasPath, SortableTrait, HasSlug, HasFactory, SoftDeletes {
        getRules as protected getRulesTrait;
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'order_column',
    ];

    protected $hidden = [
        'order_column',
        'created_at',
        'updated_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'order_column' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules['create'] = array_merge($rules['create'], [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags')->where(function ($query) {
                    $query->where('deleted_at', null);
                })
            ],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('tags')->where(function ($query) {
                    $query->where('deleted_at', null);
                })->ignore($this->id, 'id')
            ],
        ]);
        return $rules;
    }

    #[\Override]
    public function getPath(): ?string
    {
        return null;
    }

    public function scopeWithType(Builder $query, ?string $type = null)
    {
        if (!is_null($type)) {
            $query->where('type', $type)->ordered();
        }
    }

    public function scopeContaining(Builder $query, string $name, $locale = null)
    {
        // if (is_null($locale)) {
        //     $locale = static::getLocale();
        // }
        $query->whereRaw('lower(' . $this->getQuery()->getGrammar()->wrap('name') . ') like ?', ['%' . mb_strtolower($name) . '%']);
    }

    public static function findOrCreate(
        string | array | ArrayAccess $values,
        string | null $type = null,
        string | null $locale = null,
    ): Collection | Tag | static {
        $tags = collect($values)->map(function ($value) use ($type, $locale) {
            // if (is_null($locale)) {
            //     $locale = static::getLocale();
            // }

            if ($value instanceof self) {
                return $value;
            }

            return static::findOrCreateFromString($value, $type);
        });

        return is_string($values) ? $tags->first() : $tags;
    }

    public static function getWithType(string $type): DbCollection
    {
        return static::withType($type)->get();
    }

    public static function findFromString(string $name, ?string $type = null)
    {
        $locale ?? static::getLocale();

        return static::query()
            ->where('type', $type)
            ->where(function ($query) use ($name) {
                $query->where("name", $name)
                    ->orWhere("slug", $name);
            })
            ->first();
    }

    public static function findFromStringOfAnyType(string $name)
    {
        return static::query()
            ->where("name", $name)
            ->orWhere("slug", $name)
            ->get();
    }

    public static function findOrCreateFromString(string $name, ?string $type = null)
    {
        $tag = static::findFromString($name, $type);

        if (! $tag) {
            return static::create([
                'name' => $name,
                'type' => $type,
            ]);
        }

        return $tag;
    }

    public static function getTypes(): Collection
    {
        return static::groupBy('type')->pluck('type');
    }

    #[\Override]
    public function setAttribute($key, $value)
    {
        return parent::setAttribute($key, $value);
    }
}
