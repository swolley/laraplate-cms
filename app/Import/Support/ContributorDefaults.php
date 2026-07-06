<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Modules\CMS\Import\Dto\ImportContributorDto;
use Modules\CMS\Import\Upserters\ContributorUpserter;
use Modules\CMS\Models\Contributor;

final class ContributorDefaults
{
    public function __construct(
        private readonly ExternalReferenceLocator $locator,
        private readonly ContributorUpserter $contributor_upserter,
        private readonly ImportIdMap $id_map,
    ) {}

    public function resolveContributorId(): int
    {
        /** @var array{external_id: int, name: string, slug: string} $config */
        $config = config('cms.import.default_contributor', [
            'external_id' => 0,
            'name' => 'Redazione',
            'slug' => 'redazione',
        ]);

        if ($config['external_id'] > 0) {
            $existing = $this->id_map->resolve('contributors', $config['external_id'])
                ?? $this->locator->findContributorId($config['external_id'], 'cms_default');

            if ($existing !== null) {
                return $existing;
            }
        }

        $existing_by_name = Contributor::query()->where('name', $config['name'])->value('id');

        if ($existing_by_name !== null) {
            return (int) $existing_by_name;
        }

        $dto = new ImportContributorDto(
            externalId: max(1, $config['external_id']),
            name: $config['name'],
            slug: $config['slug'],
            components: [],
            sharedComponents: ImportMetadata::externalReference(
                max(1, $config['external_id']),
                'cms_default',
            ),
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
            sourceType: 'cms_default',
        );

        return $this->contributor_upserter->upsert($dto);
    }
}
