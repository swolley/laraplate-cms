<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Database\ConnectionInterface;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Translations\ContributorTranslation;

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
    private readonly Contributor $contributor;

    public function __construct(
        private readonly string $locale,
        ?Contributor $contributor = null,
    ) {
        $this->contributor = $contributor ?? new Contributor;
    }

    public function findExisting(?string $slug, string $name, ?ImportConnectionContext $context = null): ?int
    {
        $contributor = $context?->model(Contributor::class) ?? $this->contributor;

        if ($slug !== null && $slug !== '') {
            $translation = new ContributorTranslation;

            $by_slug = $contributor->getConnection()->table($translation->getTable())
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

        $by_name = $contributor->getConnection()->table($contributor->getTable())
            ->where('name', $name)
            ->value('id');

        return $by_name !== null ? (int) $by_name : null;
    }

    /**
     * Resolve the contributor row to upsert, preferring editorial identity over a
     * stale origin mapping when Naxos reuses external ids across tenants or sources.
     */
    public function resolveImportTarget(?string $slug, string $name, ?int $origin_id, ?ImportConnectionContext $context = null): ?int
    {
        $contributor = $context?->model(Contributor::class) ?? $this->contributor;
        $matched_id = $this->findExisting($slug, $name, $context);

        if ($matched_id !== null) {
            return $matched_id;
        }

        if ($origin_id === null) {
            return null;
        }

        $origin_name = $contributor->getConnection()->table($contributor->getTable())
            ->where('id', $origin_id)
            ->value('name');

        if (! is_string($origin_name) || $origin_name === $name) {
            return $origin_id;
        }

        $name_owner_id = $contributor->getConnection()->table($contributor->getTable())
            ->where('name', $name)
            ->value('id');

        if ($name_owner_id !== null && $origin_id !== (int) $name_owner_id) {
            return (int) $name_owner_id;
        }

        return $origin_id;
    }

    private function shouldDedupByName(string $name): bool
    {
        return in_array($name, $this->dedupNames(), true);
    }

    private function connection(): ConnectionInterface
    {
        return $this->contributor->getConnection();
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
