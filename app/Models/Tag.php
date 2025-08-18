<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use ArrayAccess;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as DbCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Modules\Cms\Database\Factories\TagFactory;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Helpers\SoftDeletes;
use Modules\Core\Helpers\SortableTrait;
use Override;
use Spatie\EloquentSortable\Sortable;

/**
 * @mixin IdeHelperTag
 */
final class Tag extends Model implements Sortable
{
    use HasFactory, HasPath, HasSlug, HasValidations, SoftDeletes, SortableTrait {
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

    public static function findOrCreate(
        string|array|ArrayAccess $values,
        ?string $type = null,
    ): Collection|self {
        $tags = collect($values)->map(function ($value) use ($type) {
            if ($value instanceof self) {
                return $value;
            }

            return self::findOrCreateFromString($value, $type);
        });

        return is_string($values) ? $tags->first() : $tags;
    }

    public static function getWithType(string $type): DbCollection
    {
        return self::query()->withType($type)->get();
    }

    public static function findFromString(string $name, ?string $type = null)
    {
        return self::query()
            ->where('type', $type)
            ->where(function ($query) use ($name): void {
                $query->where('name', $name)
                    ->orWhere('slug', $name);
            })
            ->first();
    }

    /**
     * @param string $name
     * @return Collection<self>
     */
    public static function findFromStringOfAnyType(string $name): Collection
    {
        return self::query()
            ->where('name', $name)
            ->orWhere('slug', $name)
            ->get();
    }

    public static function findOrCreateFromString(string $name, ?string $type = null)
    {
        $tag = self::findFromString($name, $type);

        if (! $tag) {
            return self::create([
                'name' => $name,
                'type' => $type,
            ]);
        }

        return $tag;
    }

    public static function getTypes(): Collection
    {
        return self::groupBy('type')->pluck('type');
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules['create'] = array_merge($rules['create'], [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags')->where(function ($query): void {
                    $query->where('deleted_at', null);
                }),
            ],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('tags')->where(function ($query): void {
                    $query->where('deleted_at', null);
                })->ignore($this->id, 'id'),
            ],
        ]);

        return $rules;
    }

    #[Override]
    public function getPath(): ?string
    {
        return null;
    }

    #[Scope]
    public function ordered(Builder $query): Builder
    {
        return $query->orderBy('order_column', 'asc');
    }

    #[Scope]
    public function withType(Builder $query, ?string $type = null): void
    {
        if (! is_null($type)) {
            $query->where('type', $type)->ordered();
        }
    }

    #[Scope]
    public function containing(Builder $query, string $name, $locale = null): void
    {
        // if (is_null($locale)) {
        //     $locale = static::getLocale();
        // }
        $query->whereRaw('lower(' . $this->getQuery()->getGrammar()->wrap('name') . ') like ?', ['%' . mb_strtolower($name) . '%']);
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'order_column' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
