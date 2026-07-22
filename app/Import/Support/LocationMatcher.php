<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Database\ConnectionInterface;
use Modules\CMS\Models\Location;

/**
 * Cross-source location deduplication for import upserts.
 *
 * Origin lookup is scoped to (source_type, external_id). Editorial places such as
 * "Strà" are recreated in every Naxos tenant with a different external id: match
 * them by slug or unique name instead of violating cms_locations.name uniqueness.
 */
final class LocationMatcher
{
    private readonly Location $location;

    public function __construct(?Location $location = null)
    {
        $this->location = $location ?? new Location;
    }

    public function findExisting(?string $slug, string $name, ?ImportConnectionContext $context = null): ?int
    {
        $location = $context?->model(Location::class) ?? $this->location;

        if ($slug !== null && $slug !== '') {
            $by_slug = $location->getConnection()->table($location->getTable())
                ->where('slug', $slug)
                ->value('id');

            if ($by_slug !== null) {
                return (int) $by_slug;
            }
        }

        $by_name = $location->getConnection()->table($location->getTable())
            ->where('name', $name)
            ->value('id');

        return $by_name !== null ? (int) $by_name : null;
    }

    private function connection(): ConnectionInterface
    {
        return $this->location->getConnection();
    }
}
