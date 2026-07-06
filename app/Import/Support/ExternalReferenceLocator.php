<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Enums\CMSTables;
use Modules\Core\Enums\CoreTables;

final class ExternalReferenceLocator
{
    public function __construct(
        private readonly string $locale,
    ) {}

    public function findContentId(int $externalId, string $sourceType): ?int
    {
        return $this->findBySharedComponents(CMSTables::Contents->value, $externalId, $sourceType)
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
        return $this->findBySharedComponents(CoreTables::Taxonomies->value, $externalId, $sourceType)
            ?? DB::table(CoreTables::TaxonomiesTranslations->value)
                ->where('locale', $this->locale)
                ->where('slug', $this->importSlug($externalId, $sourceType))
                ->value('taxonomy_id');
    }

    public function findContributorId(int $externalId, string $sourceType): ?int
    {
        return $this->findBySharedComponents(CMSTables::Contributors->value, $externalId, $sourceType);
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
        return $this->findTagIdBySlug($this->importSlug($externalId, $sourceType));
    }

    public function findLocationId(string $slug): ?int
    {
        return DB::table(CMSTables::Locations->value)->where('slug', $slug)->value('id');
    }

    private function findBySharedComponents(string $table, int $externalId, string $sourceType): ?int
    {
        $id = DB::table($table)
            ->where(function (Builder $builder) use ($externalId, $sourceType): void {
                $builder->where(function (Builder $modern) use ($externalId, $sourceType): void {
                    $modern->where('shared_components->external_id', $externalId)
                        ->where('shared_components->import_source', $sourceType);
                })->orWhere(function (Builder $legacy) use ($externalId, $sourceType): void {
                    $legacy->where('shared_components->naxos_id', $externalId)
                        ->where('shared_components->import_source', $sourceType);
                });
            })
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function importSlug(int $externalId, string $sourceType): string
    {
        return 'import-' . preg_replace('/[^a-z0-9_-]+/i', '-', $sourceType) . '-' . $externalId;
    }
}
