<?php

declare(strict_types=1);

namespace Modules\CMS\Import;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\CMS\Import\Support\EntityPresetResolver;
use Modules\CMS\Import\Support\ImportEntityNames;
use Modules\CMS\Models\Contributor;
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
 * Bulk-imports CMS contributors. A contributor's `name` is a plain unique column
 * (the natural key); its `slug` is per-locale. Because a contributor is a CMS
 * dynamic-content entity, each row is anchored to the `contributors` entity's
 * default preset — an install that has not seeded that entity/preset yields a clear
 * row error rather than a broken record.
 */
final readonly class ContributorImporter implements EntityImporterInterface
{
    private const string PRESET = 'default';

    public function __construct(
        private RecordOriginRegistry $origins,
        private EntityPresetResolver $presets,
    ) {}

    #[Override]
    public function key(): string
    {
        return 'cms.contributor';
    }

    #[Override]
    public function label(): string
    {
        return 'Contributors';
    }

    /**
     * @return list<ImportField>
     */
    #[Override]
    public function fields(): array
    {
        return [
            new ImportField('name', 'Name', required: true, aliases: ['author', 'byline']),
            new ImportField('slug', 'Slug'),
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

        [$entityId, $presettableId] = $this->anchor();

        $existing = Contributor::query()->withoutGlobalScopes()->where('name', $name)->first();

        if ($existing instanceof Contributor) {
            if ($existing->trashed()) {
                $existing->reviveInMemory();
            }
            $existing->name = $name;
            $existing->save();
            $contributor = $existing;
            $outcome = ImportRowOutcome::Updated;
        } else {
            $contributor = new Contributor(['entity_id' => $entityId, 'presettable_id' => $presettableId, 'name' => $name]);
            $contributor->save();
            $outcome = ImportRowOutcome::Created;
        }

        $contributor->setTranslation($this->locale(), [
            'slug' => $slug !== '' ? $slug : Str::slug($name),
            'components' => [],
        ]);
        $contributor->save();

        $this->origins->register(
            $contributor,
            new ExternalRecordIdentity($context->sourceKey(), null, hash('sha256', (string) json_encode($row))),
            $context->session->original_filename,
        );

        return $outcome;
    }

    /**
     * The `contributors` entity + default preset ids every contributor row anchors
     * to. A missing entity/preset is surfaced as a row error.
     *
     * @return array{0: int, 1: int}
     */
    private function anchor(): array
    {
        try {
            return [
                $this->presets->entityId(ImportEntityNames::CONTRIBUTORS),
                $this->presets->presettableId(ImportEntityNames::CONTRIBUTORS, self::PRESET),
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
