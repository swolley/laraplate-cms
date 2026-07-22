<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Upserters;

use InvalidArgumentException;
use Modules\CMS\Import\Dto\ImportLocationDto;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportConnectionContext;
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

    /**
     * @return list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public function participantModelClasses(): array
    {
        return [Location::class, \Modules\Core\Models\RecordOrigin::class];
    }

    public function upsert(ImportLocationDto $dto, ?ImportConnectionContext $context = null): int
    {
        $context ??= new ImportConnectionContext(new Location);
        $location_model = $context->model(Location::class);
        $existing_id = ($dto->externalId !== null
            ? $this->reference_resolver->resolve(
                'locations',
                Location::class,
                $dto->externalId,
                $dto->sourceType,
                $context,
            )
            : null)
            ?? $this->location_matcher->findExisting($dto->slug, $dto->name, $context);

        if ($existing_id !== null) {
            $location = $location_model->newQuery()->findOrFail($existing_id);
            $location->name = $dto->name;
            $location->slug = $dto->slug;
            $location->save();
        } else {
            $location = $location_model->newQuery()->create([
                'name' => $dto->name,
                'slug' => $dto->slug,
                'country' => $this->resolvedCountry($dto),
            ]);
        }

        $location_id = (int) $location->id;

        if ($dto->externalId !== null) {
            $this->reference_resolver->remember('locations', $dto->externalId, $location_id, $dto->sourceType, $context);
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
