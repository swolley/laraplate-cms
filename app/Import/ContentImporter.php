<?php

declare(strict_types=1);

namespace Modules\CMS\Import;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\CMS\Import\Support\EntityPresetResolver;
use Modules\CMS\Import\Support\ImportEntityNames;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Tag;
use Modules\Core\Import\Contracts\EntityImporterInterface;
use Modules\Core\Import\Enums\ImportRowOutcome;
use Modules\Core\Import\Enums\OnMissingRelation;
use Modules\Core\Import\Exceptions\RowImportException;
use Modules\Core\Import\Support\ImportRowContext;
use Modules\Core\Import\Support\RecordOriginRegistry;
use Modules\Core\Import\Support\RelationValueResolver;
use Modules\Core\Import\ValueObjects\ExternalRecordIdentity;
use Modules\Core\Import\ValueObjects\ImportField;
use Modules\Core\Import\ValueObjects\ImportRelationField;
use Override;
use RuntimeException;

/**
 * Bulk-imports CMS contents and, in the same row, attaches their to-many relations
 * by natural key: tags by name, categories by slug/name, contributors by name. It
 * is the pilot for {@see RelationValueResolver} — the framework construct that turns
 * a human-readable, possibly multi-value cell ("news, politics") into a list of
 * related ids without the source ever carrying an internal id.
 *
 * The policies differ by how structural each relation is: tags are cheap folksonomy
 * and are created on the fly when unknown; categories and contributors are curated,
 * so an unknown one is a row error rather than a silent creation — import those first.
 */
final readonly class ContentImporter implements EntityImporterInterface
{
    private const string PRESET = 'default';

    public function __construct(
        private RecordOriginRegistry $origins,
        private EntityPresetResolver $presets,
        private RelationValueResolver $relations,
    ) {}

    #[Override]
    public function key(): string
    {
        return 'cms.content';
    }

    #[Override]
    public function label(): string
    {
        return 'Contents';
    }

    /**
     * @return list<ImportField>
     */
    #[Override]
    public function fields(): array
    {
        return [
            new ImportField('title', 'Title', required: true, aliases: ['name', 'headline']),
            new ImportField('slug', 'Slug'),
            ...array_map(
                static fn (ImportRelationField $relation): ImportField => $relation->toField(),
                $this->relationFields(),
            ),
        ];
    }

    /**
     * @param  array<string, string>  $row
     */
    #[Override]
    public function import(array $row, ImportRowContext $context): ImportRowOutcome
    {
        $title = mb_trim($row['title'] ?? '');
        $slug = mb_trim($row['slug'] ?? '');

        $validator = Validator::make(['title' => $title], ['title' => ['required', 'string', 'max:255']]);

        if ($validator->fails()) {
            throw RowImportException::withErrors($validator->errors()->messages());
        }

        $slug = $slug !== '' ? $slug : Str::slug($title);
        [$entityId, $presettableId] = $this->anchor();

        // Resolve the relations before touching the content so an unknown category or
        // contributor fails the row cleanly, before any write happens.
        $tagIds = $this->relations->resolve(
            $row['tags'] ?? null,
            $this->relationField('tags'),
            static fn (string $value): ?int => Tag::findFromStringOfAnyType($value)->first()?->id,
            static fn (string $value): int => (int) Tag::findOrCreateFromString($value)->id,
        );
        $categoryIds = $this->relations->resolve(
            $row['categories'] ?? null,
            $this->relationField('categories'),
            // Load the whole row (not a single column): these dynamic-content models
            // auto-eager-load a relation whose foreign key would be unretrieved under
            // a column-restricted select, tripping strict attribute access.
            static fn (string $value): ?int => self::intOrNull(
                Category::query()->withoutGlobalScopes()
                    ->whereHas('translations', static fn (Builder $query): Builder => $query->where('slug', $value)->orWhere('name', $value))
                    ->first()?->getKey(),
            ),
        );
        $contributorIds = $this->relations->resolve(
            $row['contributors'] ?? null,
            $this->relationField('contributors'),
            static fn (string $value): ?int => self::intOrNull(
                Contributor::query()->withoutGlobalScopes()->where('name', $value)->first()?->getKey(),
            ),
        );

        $existing = $this->findBySlug($slug);

        if ($existing instanceof Content) {
            if ($existing->trashed()) {
                $existing->reviveInMemory();
            }
            $content = $existing;
            $outcome = ImportRowOutcome::Updated;
        } else {
            $content = (new Content)->newInstance([
                'entity_id' => $entityId,
                'presettable_id' => $presettableId,
            ]);
            $outcome = ImportRowOutcome::Created;
        }

        // Imported content lands as a draft (no validity window): drafts are
        // write-through, so a re-import updates in place without tripping the
        // published-content approval gate. Publishing stays an explicit later step.
        $content->entity_id = $entityId;
        $content->presettable_id = $presettableId;
        $content->shared_components = [];
        $content->save();

        $content->setTranslation($this->locale(), ['title' => $title, 'slug' => $slug, 'components' => []]);
        $content->save();

        if ($tagIds !== []) {
            $content->tags()->sync($tagIds);
        }

        if ($categoryIds !== []) {
            $content->categories()->sync($categoryIds);
        }

        if ($contributorIds !== []) {
            $content->contributors()->sync($contributorIds);
        }

        $this->origins->register(
            $content,
            new ExternalRecordIdentity($context->sourceKey(), null, hash('sha256', (string) json_encode($row))),
            $context->session->original_filename,
        );

        return $outcome;
    }

    /**
     * The relation columns this importer resolves by natural key.
     *
     * @return list<ImportRelationField>
     */
    private function relationFields(): array
    {
        return [
            new ImportRelationField('tags', 'Tags', 'tags', onMissing: OnMissingRelation::Create, aliases: ['keywords']),
            new ImportRelationField('categories', 'Categories', 'categories', onMissing: OnMissingRelation::Error, aliases: ['sections', 'folders']),
            new ImportRelationField('contributors', 'Contributors', 'contributors', onMissing: OnMissingRelation::Error, aliases: ['authors', 'bylines']),
        ];
    }

    private function relationField(string $name): ImportRelationField
    {
        foreach ($this->relationFields() as $relation) {
            if ($relation->name === $name) {
                return $relation;
            }
        }

        throw new RuntimeException("Unknown relation field [{$name}].");
    }

    private function findBySlug(string $slug): ?Content
    {
        return Content::query()
            ->withoutGlobalScopes()
            ->whereHas('translations', static fn (Builder $query): Builder => $query->where('slug', $slug))
            ->first();
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function anchor(): array
    {
        try {
            return [
                $this->presets->entityId(ImportEntityNames::CONTENTS),
                $this->presets->presettableId(ImportEntityNames::CONTENTS, self::PRESET),
            ];
        } catch (RuntimeException $exception) {
            throw RowImportException::withErrors(['_' => [$exception->getMessage()]]);
        }
    }

    private static function intOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function locale(): string
    {
        $locale = config('cms.import.locale') ?? config('app.locale');

        return is_string($locale) ? $locale : 'en';
    }
}
