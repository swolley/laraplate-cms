<?php

declare(strict_types=1);

namespace Modules\CMS\Import;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\CMS\Import\Support\EntityPresetResolver;
use Modules\CMS\Import\Support\ImportEntityNames;
use Modules\CMS\Models\Category;
use Modules\Core\Import\Contracts\EntityImporterInterface;
use Modules\Core\Import\Enums\ImportRowOutcome;
use Modules\Core\Import\Exceptions\RowImportException;
use Modules\Core\Import\Support\ImportRowContext;
use Modules\Core\Import\Support\RecordOriginRegistry;
use Modules\Core\Import\ValueObjects\ExternalRecordIdentity;
use Modules\Core\Import\ValueObjects\ImportField;
use Override;
use RuntimeException;

/**
 * Bulk-imports CMS categories (a taxonomy). Name and slug are per-locale; the
 * category is a dynamic-content entity anchored to the `categories` entity's
 * default preset. A row is deduped by its slug, and an optional `parent` column
 * (a parent category's slug or name) builds the hierarchy — a named-but-unknown
 * parent is a row error rather than a silently orphaned category.
 */
final readonly class CategoryImporter implements EntityImporterInterface
{
    private const string PRESET = 'default';

    public function __construct(
        private RecordOriginRegistry $origins,
        private EntityPresetResolver $presets,
    ) {}

    #[Override]
    public function key(): string
    {
        return 'cms.category';
    }

    #[Override]
    public function label(): string
    {
        return 'Categories';
    }

    /**
     * @return list<ImportField>
     */
    #[Override]
    public function fields(): array
    {
        return [
            new ImportField('name', 'Name', required: true, aliases: ['label', 'title']),
            new ImportField('slug', 'Slug'),
            new ImportField('parent', 'Parent category', aliases: ['parent_slug', 'parent_name']),
        ];
    }

    /**
     * @param  array<string, string>  $row
     */
    #[Override]
    public function import(array $row, ImportRowContext $context): ImportRowOutcome
    {
        $name = mb_trim($row['name'] ?? '');
        $slug = mb_trim($row['slug'] ?? '');

        $validator = Validator::make(['name' => $name], ['name' => ['required', 'string', 'max:255']]);

        if ($validator->fails()) {
            throw RowImportException::withErrors($validator->errors()->messages());
        }

        $slug = $slug !== '' ? $slug : Str::slug($name);
        [$entityId, $presettableId] = $this->anchor();
        $parent = $this->resolveParent(mb_trim($row['parent'] ?? ''));

        $existing = $this->findBySlug($slug);

        if ($existing instanceof Category) {
            if ($existing->trashed()) {
                $existing->reviveInMemory();
            }
            $category = $existing;
            $outcome = ImportRowOutcome::Updated;
        } else {
            $category = (new Category)->newInstance([
                'entity_id' => $entityId,
                'presettable_id' => $presettableId,
            ]);
            $outcome = ImportRowOutcome::Created;
        }

        // Attach the parent as a fully-loaded relation (its own presettable eager
        // loaded), not just an id: the recursive-hierarchy machinery reads the
        // parent's attributes on save, and a partially-selected parent would trip
        // strict mode on `presettable_id`.
        $category->parent_id = $parent?->id;

        if ($parent instanceof Category) {
            $category->setRelation('parent', $parent);
        }

        // Mirror CategoryUpserter: set every column the dynamic-content machinery
        // reads, so the model is never left in a partial-attribute state.
        $category->is_active = true;
        $category->order_column = 0;
        $category->shared_components = [];
        $category->save();

        $category->setTranslation($this->locale(), ['name' => $name, 'slug' => $slug, 'components' => []]);
        $category->save();

        $this->origins->register(
            $category,
            new ExternalRecordIdentity($context->sourceKey(), null, hash('sha256', (string) json_encode($row))),
            $context->session->original_filename,
        );

        return $outcome;
    }

    private function findBySlug(string $slug): ?Category
    {
        return Category::query()
            ->withoutGlobalScopes()
            ->with('presettable')
            ->whereHas('translations', static fn (Builder $query): Builder => $query->where('slug', $slug))
            ->first();
    }

    /**
     * Resolve an optional parent category by its slug or name, fully loaded (with
     * its presettable). Empty → root (`null`); named-but-unknown → row error.
     */
    private function resolveParent(string $parent): ?Category
    {
        if ($parent === '') {
            return null;
        }

        $model = Category::query()
            ->withoutGlobalScopes()
            ->with('presettable')
            ->whereHas('translations', static fn (Builder $query): Builder => $query->where('slug', $parent)->orWhere('name', $parent))
            ->first();

        if (! $model instanceof Category) {
            throw RowImportException::withErrors(['parent' => ["Unknown parent category [{$parent}]."]]);
        }

        return $model;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function anchor(): array
    {
        try {
            return [
                $this->presets->entityId(ImportEntityNames::CATEGORIES),
                $this->presets->presettableId(ImportEntityNames::CATEGORIES, self::PRESET),
            ];
        } catch (RuntimeException $exception) {
            throw RowImportException::withErrors(['_' => [$exception->getMessage()]]);
        }
    }

    private function locale(): string
    {
        $locale = config('cms.import.locale') ?? config('app.locale');

        return is_string($locale) ? $locale : 'en';
    }
}
