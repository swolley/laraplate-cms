<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Modules\CMS\Import\Dto\ImportContributorDto;
use Modules\CMS\Import\Upserters\ContributorUpserter;
use Modules\CMS\Models\Contributor;

final class ContributorDefaults
{
    public function __construct(
        private readonly ImportReferenceResolver $reference_resolver,
        private readonly ContributorUpserter $contributor_upserter,
    ) {}

    public function resolveContributorId(): int
    {
        /** @var array{external_id: int, name: string, slug: string, source_type: string} $config */
        $config = config('cms.import.default_contributor', [
            'external_id' => 1,
            'name' => 'Redazione',
            'slug' => 'redazione',
            'source_type' => 'cms_default',
        ]);

        $external_id = (int) $config['external_id'];
        $source_type = (string) $config['source_type'];

        $existing = $this->reference_resolver->resolve(
            'contributors',
            Contributor::class,
            $external_id,
            $source_type,
        );

        if ($existing !== null) {
            return $existing;
        }

        $dto = new ImportContributorDto(
            externalId: $external_id,
            name: $config['name'],
            slug: $config['slug'],
            components: [],
            sharedComponents: [],
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
            sourceType: $source_type,
        );

        return $this->contributor_upserter->upsert($dto);
    }
}
