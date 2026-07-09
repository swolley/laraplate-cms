<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Support\Facades\DB;
use Modules\CMS\Enums\CMSTables;

/**
 * Cross-source location deduplication for import upserts.
 *
 * Origin lookup is scoped to (source_type, external_id). Editorial places such as
 * "Strà" are recreated in every Naxos tenant with a different external id: match
 * them by slug or unique name instead of violating cms_locations.name uniqueness.
 */
final class LocationMatcher
{
    public function findExisting(?string $slug, string $name): ?int
    {
        if ($slug !== null && $slug !== '') {
            $by_slug = DB::table(CMSTables::Locations->value)
                ->where('slug', $slug)
                ->value('id');

            if ($by_slug !== null) {
                return (int) $by_slug;
            }
        }

        $by_name = DB::table(CMSTables::Locations->value)
            ->where('name', $name)
            ->value('id');

        return $by_name !== null ? (int) $by_name : null;
    }
}
