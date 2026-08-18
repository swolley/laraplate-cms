<?php

declare(strict_types=1);

namespace Modules\CMS\Services\Map;

use Illuminate\Support\Collection;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Location;
use Modules\Core\Enums\CoreTables;

/**
 * Resolves the geo-located locations that are actually referenced by contents, each
 * with the number of contents that use it — the data the map widget and the map
 * facet selector plot. Coordinates are read from the canonical Core place row
 * (decimal latitude/longitude), so the query stays portable across drivers and
 * never needs spatial functions.
 *
 * @phpstan-type MapLocation array{id: int, name: string, latitude: float, longitude: float, contentCount: int}
 */
final class MapLocationsService
{
    /**
     * @return Collection<int, MapLocation>
     */
    public function usedLocations(?BoundingBox $bounds = null, int $limit = 500): Collection
    {
        $locations = CMSTables::Locations->value;
        $places = CoreTables::Places->value;
        $locatables = CMSTables::Locatables->value;

        $query = Location::query()->toBase()
            ->from($locations)
            ->join($places, "{$places}.id", '=', "{$locations}.place_id")
            ->join($locatables, "{$locatables}.location_id", '=', "{$locations}.id")
            ->whereNull("{$locations}.deleted_at")
            ->whereNotNull("{$places}.latitude")
            ->whereNotNull("{$places}.longitude");

        if ($bounds !== null) {
            $query->whereBetween("{$places}.latitude", [$bounds->south, $bounds->north])
                ->whereBetween("{$places}.longitude", [$bounds->west, $bounds->east]);
        }

        return $query
            ->groupBy("{$locations}.id", "{$locations}.name", "{$places}.latitude", "{$places}.longitude")
            ->orderByDesc('content_count')
            ->limit($limit)
            ->select([
                "{$locations}.id",
                "{$locations}.name",
                "{$places}.latitude",
                "{$places}.longitude",
            ])
            ->selectRaw("COUNT(DISTINCT {$locatables}.content_id) as content_count")
            ->get()
            ->map(static fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
                'contentCount' => (int) $row->content_count,
            ]);
    }
}
