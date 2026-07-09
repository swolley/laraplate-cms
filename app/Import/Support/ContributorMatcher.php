<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Support\Facades\DB;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Contributor;

/**
 * Cross-source contributor deduplication for import upserts.
 *
 * Origin lookup is always scoped to (source_type, external_id). Some editorial
 * identities such as "Redazione" are recreated in every source with a different
 * external id: match them by business slug or configured canonical name instead
 * of creating duplicates that violate the unique name constraint.
 */
final class ContributorMatcher
{
    public function __construct(
        private readonly string $locale,
    ) {}

    public function findExisting(?string $slug, string $name): ?int
    {
        if ($slug !== null && $slug !== '') {
            $by_slug = DB::table(CMSTables::ContributorsTranslations->value)
                ->where('locale', $this->locale)
                ->where('slug', $slug)
                ->value('contributor_id');

            if ($by_slug !== null) {
                return (int) $by_slug;
            }
        }

        if (! $this->shouldDedupByName($name)) {
            return null;
        }

        $by_name = DB::table(CMSTables::Contributors->value)
            ->where('name', $name)
            ->value('id');

        return $by_name !== null ? (int) $by_name : null;
    }

    /**
     * Resolve the contributor row to upsert, preferring editorial identity over a
     * stale origin mapping when Naxos reuses external ids across tenants or sources.
     */
    public function resolveImportTarget(?string $slug, string $name, ?int $origin_id): ?int
    {
        $matched_id = $this->findExisting($slug, $name);

        if ($matched_id !== null) {
            return $matched_id;
        }

        if ($origin_id === null) {
            return null;
        }

        $origin_name = DB::table(CMSTables::Contributors->value)
            ->where('id', $origin_id)
            ->value('name');

        if (! is_string($origin_name) || $origin_name === $name) {
            return $origin_id;
        }

        $name_owner_id = DB::table(CMSTables::Contributors->value)
            ->where('name', $name)
            ->value('id');

        if ($name_owner_id !== null && (int) $name_owner_id !== $origin_id) {
            return (int) $name_owner_id;
        }

        return $origin_id;
    }

    private function shouldDedupByName(string $name): bool
    {
        return in_array($name, $this->dedupNames(), true);
    }

    /**
     * @return list<string>
     */
    private function dedupNames(): array
    {
        /** @var list<string> $configured */
        $configured = config('cms.import.contributor_dedup_names', []);
        $default_name = (string) config('cms.import.default_contributor.name', '');

        return array_values(array_unique(array_filter([...$configured, $default_name])));
    }
}
