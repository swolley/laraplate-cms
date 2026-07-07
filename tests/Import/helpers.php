<?php

declare(strict_types=1);

use Modules\CMS\Import\Dto\ImportCategoryDto;
use Modules\CMS\Import\Dto\ImportContributorDto;
use Modules\CMS\Import\Dto\ImportContentDto;
use Modules\CMS\Import\Dto\ImportGraphDto;
use Modules\CMS\Import\Dto\ImportTagDto;

/**
 * @return array{content: array<string, mixed>, category: array<string, mixed>, contributor: array<string, mixed>, tag: array<string, mixed>}
 */
function loadImportFixture(string $filename = 'sample-graph.json'): array
{
    $path = __DIR__ . '/../Fixtures/Import/' . $filename;
    $json = file_get_contents($path);

    if ($json === false) {
        throw new RuntimeException("Import fixture not found: {$path}");
    }

    /** @var array{content: array<string, mixed>, category: array<string, mixed>, contributor: array<string, mixed>, tag: array<string, mixed>} $data */
    $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

    return $data;
}

function buildImportGraphFromFixture(string $filename = 'sample-graph.json'): ImportGraphDto
{
    $fixture = loadImportFixture($filename);

    $category = new ImportCategoryDto(
        externalId: (int) $fixture['category']['external_id'],
        name: (string) $fixture['category']['name'],
        slug: (string) $fixture['category']['slug'],
        parentExternalId: null,
        components: [],
        sharedComponents: [],
        isActive: true,
        orderColumn: 0,
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        sourceType: (string) $fixture['category']['source_type'],
        entityName: (string) $fixture['category']['entity_name'],
        presetName: (string) $fixture['category']['preset_name'],
    );

    $contributor = new ImportContributorDto(
        externalId: (int) $fixture['contributor']['external_id'],
        name: (string) $fixture['contributor']['name'],
        slug: (string) $fixture['contributor']['slug'],
        components: [],
        sharedComponents: [],
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        sourceType: (string) $fixture['contributor']['source_type'],
        entityName: (string) $fixture['contributor']['entity_name'],
        presetName: (string) $fixture['contributor']['preset_name'],
    );

    $tag = new ImportTagDto(
        externalId: (int) $fixture['tag']['external_id'],
        name: (string) $fixture['tag']['name'],
        slug: (string) $fixture['tag']['slug'],
        type: null,
        orderColumn: 0,
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        sourceType: (string) $fixture['tag']['source_type'],
    );

    $content = new ImportContentDto(
        title: (string) $fixture['content']['title'],
        slug: (string) $fixture['content']['slug'],
        components: [],
        sharedComponents: [],
        validFrom: now()->toDateTimeString(),
        validTo: null,
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        externalId: (int) $fixture['content']['external_id'],
        externalUuid: null,
        sourceType: (string) $fixture['content']['source_type'],
        categoryExternalIds: array_map('intval', $fixture['content']['category_external_ids']),
        contributorExternalIds: array_map('intval', $fixture['content']['contributor_external_ids']),
        tagExternalIds: array_map('intval', $fixture['content']['tag_external_ids']),
        entityName: (string) $fixture['content']['entity_name'],
        presetName: (string) $fixture['content']['preset_name'],
        sourceKind: (string) $fixture['content']['source_kind'],
    );

    return new ImportGraphDto(
        content: $content,
        categories: [$category],
        contributors: [$contributor],
        tags: [$tag],
    );
}
