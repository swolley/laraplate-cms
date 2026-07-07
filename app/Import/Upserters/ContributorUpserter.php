<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Upserters;

use Modules\CMS\Import\Dto\ImportContributorDto;
use Modules\CMS\Import\Support\EntityPresetResolver;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportIdMap;
use Modules\CMS\Models\Contributor;

final class ContributorUpserter
{
    public function __construct(
        private readonly EntityPresetResolver $entity_preset_resolver,
        private readonly ExternalReferenceLocator $locator,
        private readonly ImportIdMap $id_map,
        private readonly string $locale,
    ) {}

    public function upsert(ImportContributorDto $dto): int
    {
        $existing_id = $this->id_map->resolve('contributors', $dto->externalId)
            ?? $this->locator->findContributorId($dto->externalId, $dto->sourceType);

        $entity_id = $this->entity_preset_resolver->entityId($dto->entityName);
        $presettable_id = $this->entity_preset_resolver->presettableId($dto->entityName, $dto->presetName);

        if ($existing_id !== null) {
            $contributor = Contributor::query()->withoutGlobalScopes()->findOrFail($existing_id);
        } else {
            $contributor = new Contributor([
                'entity_id' => $entity_id,
                'presettable_id' => $presettable_id,
                'name' => $dto->name,
            ]);
        }

        $contributor->name = $dto->name;
        $contributor->shared_components = $dto->sharedComponents;
        $contributor->save();

        if ($dto->slug !== null && $dto->slug !== '') {
            $contributor->setTranslation($this->locale, [
                'slug' => $dto->slug,
                'components' => $dto->components,
            ]);
            $contributor->save();
        }

        if ($dto->deletedAt !== null && ! $contributor->trashed()) {
            $contributor->delete();
        }

        $contributor_id = (int) $contributor->id;
        $this->id_map->remember('contributors', $dto->externalId, $contributor_id);

        $this->locator->register($contributor, $dto->sourceType, $dto->externalId);

        return $contributor_id;
    }
}
