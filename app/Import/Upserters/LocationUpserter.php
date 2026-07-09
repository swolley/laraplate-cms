<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Upserters;

use Modules\CMS\Import\Dto\ImportLocationDto;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportReferenceResolver;
use Modules\CMS\Models\Location;

final class LocationUpserter
{
    public function __construct(
        private readonly ExternalReferenceLocator $locator,
        private readonly ImportReferenceResolver $reference_resolver,
    ) {}

    public function upsert(ImportLocationDto $dto): int
    {
        $existing_id = $dto->externalId !== null
            ? $this->reference_resolver->resolve(
                'locations',
                Location::class,
                $dto->externalId,
                $dto->sourceType,
            )
            : null;

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
            $this->reference_resolver->remember('locations', $dto->externalId, $location_id);
            $this->locator->register($location, $dto->sourceType, $dto->externalId);
        }

        return $location_id;
    }
}
