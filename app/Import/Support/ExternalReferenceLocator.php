<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Tag;
use Modules\Core\Enums\CoreTables;

/**
 * Resolves and registers import identity/provenance through the generic
 * {@see \Modules\Core\Models\RecordOrigin} registry (core_record_origins).
 */
final class ExternalReferenceLocator
{
    public function __construct(
        private readonly string $locale,
    ) {}

    public function findContentId(int $externalId, string $sourceType): ?int
    {
        return $this->findByOrigin(Content::class, $externalId, $sourceType)
            ?? DB::table(CMSTables::ContentsTranslations->value)
                ->where('locale', $this->locale)
                ->where('slug', $this->importSlug($externalId, $sourceType))
                ->value('content_id');
    }

    public function findContentIdBySlug(string $slug): ?int
    {
        return DB::table(CMSTables::ContentsTranslations->value)
            ->where('locale', $this->locale)
            ->where('slug', $slug)
            ->value('content_id');
    }

    public function findCategoryId(int $externalId, string $sourceType): ?int
    {
        return $this->findByOrigin(Category::class, $externalId, $sourceType)
            ?? DB::table(CoreTables::TaxonomiesTranslations->value)
                ->where('locale', $this->locale)
                ->where('slug', $this->importSlug($externalId, $sourceType))
                ->value('taxonomy_id');
    }

    public function findContributorId(int $externalId, string $sourceType): ?int
    {
        return $this->findByOrigin(Contributor::class, $externalId, $sourceType);
    }

    public function findTagIdBySlug(string $slug): ?int
    {
        $tag_id = DB::table(CMSTables::TagsTranslations->value)
            ->where('locale', $this->locale)
            ->where('slug', $slug)
            ->value('tag_id');

        return $tag_id !== null ? (int) $tag_id : null;
    }

    public function findTagId(int $externalId, string $sourceType): ?int
    {
        return $this->findByOrigin(Tag::class, $externalId, $sourceType)
            ?? $this->findTagIdBySlug($this->importSlug($externalId, $sourceType));
    }

    public function findLocationId(string $slug): ?int
    {
        return DB::table(CMSTables::Locations->value)->where('slug', $slug)->value('id');
    }

    /**
     * Persist (or refresh) the origin of a record in the registry. Keyed by the
     * external identity so repeated imports remain idempotent.
     */
    public function register(
        Model $referable,
        string $sourceKey,
        ?int $externalId,
        ?string $sourceLabel = null,
        ?string $url = null,
    ): void {
        $now = now();
        $external = $externalId !== null ? (string) $externalId : null;

        $query = DB::table(CoreTables::RecordOrigins->value)
            ->where('referable_type', $referable->getMorphClass())
            ->where('source_key', $sourceKey)
            ->when(
                $external !== null,
                fn ($q) => $q->where('external_id', $external),
                fn ($q) => $q->whereNull('external_id')->where('referable_id', $referable->getKey()),
            );

        $existing_id = $query->value('id');

        $values = [
            'referable_id' => $referable->getKey(),
            'source_label' => $sourceLabel,
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
            'source_key' => $sourceKey,
            'external_id' => $external,
            'created_at' => $now,
        ]);
    }

    /**
     * @param  class-string<Model>  $referableClass
     */
    private function findByOrigin(string $referableClass, int $externalId, string $sourceType): ?int
    {
        $id = DB::table(CoreTables::RecordOrigins->value)
            ->where('referable_type', $referableClass)
            ->where('source_key', $sourceType)
            ->where('external_id', (string) $externalId)
            ->value('referable_id');

        return $id !== null ? (int) $id : null;
    }

    private function importSlug(int $externalId, string $sourceType): string
    {
        return 'import-' . preg_replace('/[^a-z0-9_-]+/i', '-', $sourceType) . '-' . $externalId;
    }
}
