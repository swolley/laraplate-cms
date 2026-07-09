<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Models\Tag;
use Modules\Core\Enums\CoreTables;

/**
 * Resolves and registers import identity/provenance through the generic
 * {@see \Modules\Core\Models\RecordOrigin} registry (core_record_origins).
 */
final class ExternalReferenceLocator
{
    /**
     * Translation tables used for the deterministic import-slug fallback.
     *
     * @var array<class-string<Model>, array{table: string, foreign_key: string}>
     */
    private const IMPORT_SLUG_TARGETS = [
        Content::class => [
            'table' => CMSTables::ContentsTranslations->value,
            'foreign_key' => 'content_id',
        ],
        Category::class => [
            'table' => CoreTables::TaxonomiesTranslations->value,
            'foreign_key' => 'taxonomy_id',
        ],
        Contributor::class => [
            'table' => CMSTables::ContributorsTranslations->value,
            'foreign_key' => 'contributor_id',
        ],
        Tag::class => [
            'table' => CMSTables::TagsTranslations->value,
            'foreign_key' => 'tag_id',
        ],
    ];

    public function __construct(
        private readonly string $locale,
    ) {}

    /**
     * @param  class-string<Model>  $referable_class
     */
    public function findImportedRecordId(string $referable_class, int $external_id, string $source_type): ?int
    {
        return $this->findByOrigin($referable_class, $external_id, $source_type)
            ?? $this->findByImportSlug($referable_class, $external_id, $source_type);
    }

    /**
     * @param  class-string<Model>  $referable_class
     */
    public function hasImportedRecord(string $referable_class, int $external_id, string $source_type): bool
    {
        return $this->findImportedRecordId($referable_class, $external_id, $source_type) !== null;
    }

    /**
     * Persist (or refresh) the origin of a record in the registry. Keyed by the
     * external identity so repeated imports remain idempotent.
     */
    public function register(
        Model $referable,
        string $source_key,
        ?int $external_id,
        ?string $source_label = null,
        ?string $url = null,
    ): void {
        $now = now();
        $external = $external_id !== null ? (string) $external_id : null;

        $query = DB::table(CoreTables::RecordOrigins->value)
            ->where('referable_type', $referable->getMorphClass())
            ->where('source_key', $source_key)
            ->when(
                $external !== null,
                fn ($q) => $q->where('external_id', $external),
                fn ($q) => $q->whereNull('external_id')->where('referable_id', $referable->getKey()),
            );

        $existing_id = $query->value('id');

        $values = [
            'referable_id' => $referable->getKey(),
            'source_label' => $source_label,
            'url' => $url,
            'updated_at' => $now,
        ];

        if ($existing_id !== null) {
            DB::table(CoreTables::RecordOrigins->value)->where('id', $existing_id)->update($values);

            return;
        }

        DB::table(CoreTables::RecordOrigins->value)->insert([
            ...$values,
            'referable_type' => $referable->getMorphClass(),
            'source_key' => $source_key,
            'external_id' => $external,
            'created_at' => $now,
        ]);
    }

    public function importSlug(int $external_id, string $source_type): string
    {
        return 'import-' . preg_replace('/[^a-z0-9_-]+/i', '-', $source_type) . '-' . $external_id;
    }

    /**
     * @param  class-string<Model>  $referable_class
     */
    private function findByOrigin(string $referable_class, int $external_id, string $source_type): ?int
    {
        $id = DB::table(CoreTables::RecordOrigins->value)
            ->where('referable_type', $referable_class)
            ->where('source_key', $source_type)
            ->where('external_id', (string) $external_id)
            ->value('referable_id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * @param  class-string<Model>  $referable_class
     */
    private function findByImportSlug(string $referable_class, int $external_id, string $source_type): ?int
    {
        if ($referable_class === Location::class) {
            return null;
        }

        $target = self::IMPORT_SLUG_TARGETS[$referable_class] ?? null;

        if ($target === null) {
            return null;
        }

        $local_id = DB::table($target['table'])
            ->where('locale', $this->locale)
            ->where('slug', $this->importSlug($external_id, $source_type))
            ->value($target['foreign_key']);

        return $local_id !== null ? (int) $local_id : null;
    }
}
