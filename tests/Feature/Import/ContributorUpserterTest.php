<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Import\Dto\ImportContributorDto;
use Modules\CMS\Import\Support\ImportEntityNames;
use Modules\CMS\Import\Upserters\ContributorUpserter;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\RecordOrigin;
use Modules\Core\Services\DynamicContentsService;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config(['scout.driver' => 'null']);

    if (! Schema::hasTable(CMSTables::Contributors->value)) {
        $this->markTestSkipped('CMS contributor upserter tests require full schema.');
    }

    setupCMSEntities([EntityType::Contributors]);
    DynamicContentsService::reset();
});

it('reuses an existing contributor from another source when slug matches', function (): void {
    config(['cms.import.locale' => 'it']);

    $existing = Contributor::factory()->create(['name' => 'Redazione']);
    $existing->setTranslation('it', ['slug' => 'redazione', 'components' => []]);
    $existing->save();

    resolve(\Modules\CMS\Import\Support\ExternalReferenceLocator::class)
        ->register($existing, 'naxos_api', 74);

    $dto = new ImportContributorDto(
        externalId: 12,
        name: 'Redazione',
        slug: 'redazione',
        components: [],
        sharedComponents: [],
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        sourceType: 'second_source',
        entityName: ImportEntityNames::CONTRIBUTORS,
        presetName: 'default',
    );

    $resolved_id = resolve(ContributorUpserter::class)->upsert($dto);

    expect($resolved_id)->toBe($existing->id)
        ->and(Contributor::query()->count())->toBe(1)
        ->and(RecordOrigin::query()
            ->where('referable_type', Contributor::class)
            ->where('referable_id', $existing->id)
            ->where('source_key', 'second_source')
            ->where('external_id', '12')
            ->exists())->toBeTrue();
});

it('reuses an existing contributor from another source when name is configured for dedup', function (): void {
    config([
        'cms.import.locale' => 'it',
        'cms.import.contributor_dedup_names' => ['Redazione'],
    ]);

    $existing = Contributor::factory()->create(['name' => 'Redazione']);
    $existing->setTranslation('it', ['slug' => 'redazione-naxos', 'components' => []]);
    $existing->save();

    resolve(\Modules\CMS\Import\Support\ExternalReferenceLocator::class)
        ->register($existing, 'naxos_api', 74);

    $dto = new ImportContributorDto(
        externalId: 12,
        name: 'Redazione',
        slug: 'redazione-altro',
        components: [],
        sharedComponents: [],
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        sourceType: 'second_source',
        entityName: ImportEntityNames::CONTRIBUTORS,
        presetName: 'default',
    );

    $resolved_id = resolve(ContributorUpserter::class)->upsert($dto);

    expect($resolved_id)->toBe($existing->id)
        ->and(Contributor::query()->count())->toBe(1);
});

it('does not match contributors by name when they are not configured for dedup', function (): void {
    config([
        'cms.import.contributor_dedup_names' => ['Redazione'],
        'cms.import.default_contributor.name' => 'Redazione',
    ]);

    Contributor::factory()->create(['name' => 'Mario Rossi']);

    $matcher = resolve(\Modules\CMS\Import\Support\ContributorMatcher::class);

    expect($matcher->findExisting('mario-rossi-b', 'Mario Rossi'))->toBeNull();
});
