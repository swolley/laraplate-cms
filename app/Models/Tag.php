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
use Modules\Cms\Database\Factories\TagFactory;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Core\Helpers\HasTranslations;
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
    use HasFactory;
    use HasPath;
    use HasSlug;
    use HasTranslations;
    use HasValidations {
        getRules as protected getRulesTrait;
    }
    use SoftDeletes;
    use SortableTrait;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        // name, slug are now in translations table
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
            $tag = self::query()->create([
                'type' => $type,
            ]);
            // Set name in default locale translation
            $tag->setTranslation(config('app.locale'), [
                'name' => $name,
            ]);

            return $tag;
        }

        return $tag;
    }

    public static function getTypes(): Collection
    {
        return self::query()->groupBy('type')->pluck('type');
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules['create'] = array_merge($rules['create'], [
            'name' => 'required|string|max:255', // Validated in translation
            'slug' => 'sometimes|nullable|string|max:255', // Validated in translation
            'translations' => 'sometimes|array',
            'translations.*.locale' => 'required|string|max:10',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.slug' => 'sometimes|nullable|string|max:255',
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => 'sometimes|required|string|max:255', // Validated in translation
            'slug' => 'sometimes|nullable|string|max:255', // Validated in translation
            'translations' => 'sometimes|array',
            'translations.*.locale' => 'required|string|max:10',
            'translations.*.name' => 'sometimes|required|string|max:255',
            'translations.*.slug' => 'sometimes|nullable|string|max:255',
        ]);

        return $rules;
    }

    #[Override]
    public function getPath(): ?string
    {
        return null;
    }

    #[Override]
    public function toArray(): array
    {
        $array = parent::toArray();

        // Merge translatable fields from translation (trasparente)
        $translation = $this->getRelationValue('translation');

        if ($translation) {
            foreach ($this->getTranslatableFields() as $field) {
                if (isset($translation->{$field})) {
                    $array[$field] = $translation->{$field};
                }
            }
        }

        return $array;
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('order_column', 'asc');
    }

    #[Scope]
    protected function withType(Builder $query, ?string $type = null): void
    {
        if (! is_null($type)) {
            $query->where('type', $type)->ordered();
        }
    }

    #[Scope]
    protected function containing(Builder $query, string $name, $locale = null): void
    {
        // if (is_null($locale)) {
        //     $locale = static::getLocale();
        // }
        $query->whereRaw('lower(' . $this->getQuery()->getGrammar()->wrap('name') . ') like ?', ['%' . mb_strtolower($name) . '%']);
    }

    protected function casts(): array
    {
        return [
            'order_column' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected function slugFields(): array
    {
        // Use name from translation
        return ['name'];
    }
}
