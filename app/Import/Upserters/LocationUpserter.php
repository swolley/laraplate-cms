<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Upserters;

use InvalidArgumentException;
use Modules\CMS\Import\Dto\ImportLocationDto;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportReferenceResolver;
use Modules\CMS\Import\Support\LocationMatcher;
use Modules\CMS\Models\Location;

final class LocationUpserter
{
    public function __construct(
        private readonly ExternalReferenceLocator $locator,
        private readonly ImportReferenceResolver $reference_resolver,
        private readonly LocationMatcher $location_matcher,
    ) {}

    public function upsert(ImportLocationDto $dto): int
    {
        $existing_id = ($dto->externalId !== null
            ? $this->reference_resolver->resolve(
                'locations',
                Location::class,
                $dto->externalId,
                $dto->sourceType,
            )
            : null)
            ?? $this->location_matcher->findExisting($dto->slug, $dto->name);

        if ($existing_id !== null) {
            $location = Location::query()->findOrFail($existing_id);
            $location->name = $dto->name;
            $location->slug = $dto->slug;
            $location->save();
        } else {
            $location = Location::query()->create([
                'name' => $dto->name,
                'slug' => $dto->slug,
                'country' => $this->resolvedCountry($dto),
            ]);
        }

        $location_id = (int) $location->id;

        if ($dto->externalId !== null) {
            $this->reference_resolver->remember('locations', $dto->externalId, $location_id);
            $this->locator->register($location, $dto->sourceType, $dto->externalId);
        }

        return $location_id;
    }

    private function resolvedCountry(ImportLocationDto $dto): string
    {
        $country = $dto->country;

        if (! is_string($country) || $country === '') {
            throw new InvalidArgumentException(
                'ImportLocationDto::country is required. Source importers must provide it.',
            );
        }

        return $country;
    }
}
