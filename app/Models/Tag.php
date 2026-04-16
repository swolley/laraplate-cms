<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use ArrayAccess;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as DbCollection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Modules\Cms\Database\Factories\TagFactory;
use Modules\Core\Helpers\HasPath;
use Modules\Core\Helpers\HasTranslations;
use Modules\Core\Helpers\SortableTrait;
use Modules\Core\Overrides\Model;
use Override;
use Spatie\EloquentSortable\Sortable;

/**
 * @mixin IdeHelperTag
 */
final class Tag extends Model implements Sortable
{
    use HasPath;
    use HasTranslations;
    use SortableTrait;

    /**
     * The attributes that are mass assignable.
     */
    #[Override]
    protected $fillable = [
        // name, slug are now in translations table
        'type',
        'order_column',
    ];

    #[Override]
    protected $hidden = [
        'order_column',
        'created_at',
        'updated_at',
    ];

    public static function findOrCreate(
        string|array|ArrayAccess $values,
        ?string $type = null,
    ): Collection|self {
        $tags = collect($values)->map(function (self|string $value) use ($type): self {
            // @codeCoverageIgnoreStart
            if ($value instanceof self) {
                return $value;
            }
            // @codeCoverageIgnoreEnd

            return self::findOrCreateFromString($value, $type);
        });

        return is_string($values) ? $tags->first() : $tags;
    }

    public static function getWithType(string $type): DbCollection
    {
        return self::query()->withType($type)->get();
    }

    public static function findFromString(string $name, ?string $type = null): ?self
    {
        return self::query()
            ->where('type', $type)
            ->whereHas('translations', static function (Builder $query) use ($name): void {
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
            ->whereHas('translations', static function (Builder $query) use ($name): void {
                $query->where('name', $name)
                    ->orWhere('slug', $name);
            })
            ->get();
    }

    public static function findOrCreateFromString(string $name, ?string $type = null): self
    {
        $tag = self::findFromString($name, $type);

        if (! $tag instanceof self) {
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

    /**
     * Contents that reference this tag via the taggables morph pivot.
     *
     * @return MorphToMany<Content, $this>
     */
    public function contents(): MorphToMany
    {
        return $this->morphedByMany(Content::class, 'taggable', 'taggables');
    }

    /**
     * Determine if model should be sorted when creating.
     */
    public function shouldSortWhenCreating(): bool
    {
        return true;
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules['create'] = array_merge($rules['create'], [
            // 'name' => 'required|string|max:255', // Validated in translation
            // 'slug' => 'sometimes|nullable|string|max:255', // Validated in translation
            'translations' => 'sometimes|array',
            'translations.*.locale' => 'required|string|max:10',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.slug' => 'sometimes|nullable|string|max:255',
        ]);
        $rules['update'] = array_merge($rules['update'], [
            // 'name' => 'sometimes|required|string|max:255', // Validated in translation
            // 'slug' => 'sometimes|nullable|string|max:255', // Validated in translation
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
            foreach ($this::getTranslatableFields() as $field) {
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

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function withType(Builder $query, ?string $type = null): void
    {
        if (! is_null($type)) {
            $query->where('type', $type)->ordered();
        }
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function containing(Builder $query, string $name, ?string $locale = null): void
    {
        $resolved_locale = $locale ?? $this->getCurrentLocale();

        self::applyContainingByTranslatedName($query, $name, $resolved_locale);
    }

    protected function casts(): array
    {
        return [
            'order_column' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    protected function dynamicSlugFields(): array
    {
        return [];
    }

    protected function slugPlaceholders(): array
    {
        return [...array_map(fn (string $field): string => '{' . $field . '}', $this->dynamicSlugFields()), '{name}'];
    }

    /**
     * @param  Builder<static>  $query
     */
    private static function applyContainingByTranslatedName(Builder $query, string $name, string $locale): void
    {
        $needle = '%' . mb_strtolower($name) . '%';

        $query->whereHas('translations', function (Builder $translation_query) use ($needle, $locale): void {
            $translation_query->where('locale', $locale)
                ->whereRaw('lower(name) like ?', [$needle]);
        });
    }
}
