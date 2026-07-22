<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Upserters;

use Modules\CMS\Import\Dto\ImportContributorDto;
use Modules\CMS\Import\Support\ContributorMatcher;
use Modules\CMS\Import\Support\EntityPresetResolver;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportConnectionContext;
use Modules\CMS\Import\Support\ImportReferenceResolver;
use Modules\CMS\Models\Contributor;

final class ContributorUpserter
{
    public function __construct(
        private readonly EntityPresetResolver $entity_preset_resolver,
        private readonly ExternalReferenceLocator $locator,
        private readonly ImportReferenceResolver $reference_resolver,
        private readonly ContributorMatcher $contributor_matcher,
        private readonly string $locale,
    ) {}

    /**
     * @return list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public function participantModelClasses(): array
    {
        return [Contributor::class, \Modules\CMS\Models\Translations\ContributorTranslation::class, \Modules\Core\Models\RecordOrigin::class];
    }

    public function upsert(ImportContributorDto $dto, ?ImportConnectionContext $context = null): int
    {
        $context ??= new ImportConnectionContext(new Contributor);
        $contributor_model = $context->model(Contributor::class);
        $origin_id = $this->reference_resolver->resolve(
            'contributors',
            Contributor::class,
            $dto->externalId,
            $dto->sourceType,
            $context,
        );

        $existing_id = $this->contributor_matcher->resolveImportTarget(
            $dto->slug,
            $dto->name,
            $origin_id,
            $context,
        );

        $entity_id = $this->entity_preset_resolver->entityId($dto->entityName, $context);
        $presettable_id = $this->entity_preset_resolver->presettableId($dto->entityName, $dto->presetName, $context);

        if ($existing_id !== null) {
            $contributor = $contributor_model->newQueryWithoutScopes()->findOrFail($existing_id);
        } else {
            $contributor = $contributor_model->newInstance([
                'entity_id' => $entity_id,
                'presettable_id' => $presettable_id,
                'name' => $dto->name,
            ]);
        }

        // A record that reappears in the source must be revived before it can be
        // updated: soft-deleted models reject updates ("Cannot update a softdeleted
        // model"). reviveInMemory() lets the save() below persist the restoration in
        // a single write. If the source still marks it deleted, it is re-deleted below.
        if ($contributor->exists && $contributor->trashed()) {
            $contributor->reviveInMemory();
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
        $this->reference_resolver->remember('contributors', $dto->externalId, $contributor_id, $dto->sourceType, $context);

        $this->locator->register($contributor, $dto->sourceType, $dto->externalId);

        return $contributor_id;
    }
}
