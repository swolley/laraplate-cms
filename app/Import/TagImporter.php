<?php

declare(strict_types=1);

namespace Modules\CMS\Import;

use Illuminate\Support\Facades\Validator;
use Modules\CMS\Models\Tag;
use Modules\Core\Import\Contracts\EntityImporterInterface;
use Modules\Core\Import\Enums\ImportRowOutcome;
use Modules\Core\Import\Exceptions\RowImportException;
use Modules\Core\Import\Support\ImportRowContext;
use Modules\Core\Import\Support\RecordOriginRegistry;
use Modules\Core\Import\ValueObjects\ExternalRecordIdentity;
use Modules\Core\Import\ValueObjects\ImportField;
use Override;

/**
 * Bulk-imports CMS tags. A tag's name lives in a per-locale translation, so a row
 * is matched (and deduped) by its translated name within its `type`, and the name
 * is written into the import locale. Re-importing the same tag updates its name
 * rather than creating a duplicate.
 */
final readonly class TagImporter implements EntityImporterInterface
{
    public function __construct(private RecordOriginRegistry $origins) {}

    #[Override]
    public function key(): string
    {
        return 'cms.tag';
    }

    #[Override]
    public function label(): string
    {
        return 'Tags';
    }

    /**
     * @return list<ImportField>
     */
    #[Override]
    public function fields(): array
    {
        return [
            new ImportField('name', 'Name', required: true, aliases: ['label', 'title']),
            new ImportField('type', 'Type', aliases: ['group', 'category']),
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
        $type = mb_trim($row['type'] ?? '') ?: null;
        $slug = mb_trim($row['slug'] ?? '');

        $validator = Validator::make(['name' => $name], ['name' => ['required', 'string', 'max:255']]);

        if ($validator->fails()) {
            throw RowImportException::withErrors($validator->errors()->messages());
        }

        $existing = Tag::findFromString($name, $type);
        $tag = $existing ?? Tag::query()->create(['type' => $type]);

        $translation = ['name' => $name];

        if ($slug !== '') {
            $translation['slug'] = $slug;
        }

        $tag->setTranslation($this->locale(), $translation);

        $this->origins->register(
            $tag,
            new ExternalRecordIdentity($context->sourceKey(), null, hash('sha256', (string) json_encode($row))),
            $context->session->original_filename,
        );

        return $existing instanceof Tag ? ImportRowOutcome::Updated : ImportRowOutcome::Created;
    }

    private function locale(): string
    {
        $locale = config('cms.import.locale') ?? config('app.locale');

        return is_string($locale) ? $locale : 'en';
    }
}
