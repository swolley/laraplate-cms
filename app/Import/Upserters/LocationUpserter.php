<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Upserters;

use Modules\CMS\Import\Dto\ImportLocationDto;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportIdMap;
use Modules\CMS\Models\Location;

final class LocationUpserter
{
    public function __construct(
        private readonly ExternalReferenceLocator $locator,
        private readonly ImportIdMap $id_map,
    ) {}

    public function upsert(ImportLocationDto $dto): int
    {
        $existing_id = $dto->externalId !== null
            ? ($this->id_map->resolve('locations', $dto->externalId) ?? $this->locator->findLocationId($dto->slug))
            : $this->locator->findLocationId($dto->slug);

        if ($existing_id !== null) {
            $location = Location::query()->findOrFail($existing_id);
            $location->name = $dto->name;
            $location->slug = $dto->slug;
            $location->save();
        } else {
            $location = Location::query()->create([
                'name' => $dto->name,
                'slug' => $dto->slug,
            ]);
        }

        $location_id = (int) $location->id;

        if ($dto->externalId !== null) {
            $this->id_map->remember('locations', $dto->externalId, $location_id);
        }

        return $location_id;
    }
}
