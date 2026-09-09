<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\CMS\Import\Dto\ImportCategoryDto;
use Modules\CMS\Import\Dto\ImportContributorDto;
use Modules\CMS\Import\Dto\ImportLocationDto;
use Modules\CMS\Import\Dto\ImportTagDto;
use Modules\CMS\Import\Pipeline\ImportPipeline;
use Modules\CMS\Import\Support\ImportIdMap;
use Modules\CMS\Import\Upserters\CategoryUpserter;
use Modules\CMS\Import\Upserters\ContributorUpserter;
use Modules\CMS\Import\Upserters\LocationUpserter;
use Modules\CMS\Import\Upserters\TagUpserter;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Import\Support\ImportFingerprint;
use Modules\Core\Models\RecordOrigin;
use Modules\Core\Services\DynamicContentsService;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config(['scout.driver' => 'null']);
    Queue::fake();

    $category = new Category;

    if (! $category->getConnection()->getSchemaBuilder()->hasTable($category->getTable())) {
        $this->markTestSkipped('CMS import tests require full schema.');
    }

    setupCMSEntities();
    DynamicContentsService::reset();
});

function origin_for(string $referable_type, string $external_id): ?RecordOrigin
{
    return RecordOrigin::query()
        ->where('referable_type', $referable_type)
        ->where('external_id', $external_id)
        ->first();
}

it('records label, url, fingerprint and source timestamp for an imported category', function (): void {
    $dto = new ImportCategoryDto(
        externalId: 2001,
        name: 'Esteri',
        slug: 'esteri',
        parentExternalId: null,
        components: [],
        sharedComponents: [],
        isActive: true,
        orderColumn: 0,
        createdAt: null,
        updatedAt: '2026-09-01 10:30:00',
        deletedAt: null,
        sourceType: 'fixture@tenant',
        originLabel: 'naxos.example.it',
        originUrl: 'https://naxos.example.it/esteri',
    );

    resolve(CategoryUpserter::class)->upsert($dto);

    $origin = origin_for(Category::class, '2001');

    expect($origin)->not->toBeNull()
        ->and($origin->source_label)->toBe('naxos.example.it')
        ->and($origin->url)->toBe('https://naxos.example.it/esteri')
        ->and($origin->fingerprint)->toBe(ImportFingerprint::of($dto))
        ->and($origin->source_updated_at?->toDateTimeString())->toBe('2026-09-01 10:30:00');
});

it('records the origin of an imported tag', function (): void {
    resolve(TagUpserter::class)->upsert(new ImportTagDto(
        externalId: 4001,
        name: 'Elezioni',
        slug: 'elezioni',
        type: null,
        orderColumn: 0,
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        sourceType: 'fixture@tenant',
        originLabel: 'naxos.example.it',
        originUrl: 'https://naxos.example.it/tag/elezioni',
    ));

    $origin = origin_for(Tag::class, '4001');

    expect($origin)->not->toBeNull()
        ->and($origin->source_label)->toBe('naxos.example.it')
        ->and($origin->url)->toBe('https://naxos.example.it/tag/elezioni')
        ->and($origin->fingerprint)->not->toBeNull()
        ->and($origin->source_updated_at)->toBeNull();
});

it('records the origin of an imported contributor', function (): void {
    resolve(ContributorUpserter::class)->upsert(new ImportContributorDto(
        externalId: 3001,
        name: 'Giulia Rossi',
        slug: 'giulia-rossi',
        components: [],
        sharedComponents: [],
        createdAt: null,
        updatedAt: '2026-08-30 08:00:00',
        deletedAt: null,
        sourceType: 'fixture@tenant',
        originLabel: 'naxos.example.it',
        originUrl: 'https://naxos.example.it/autori/giulia-rossi',
    ));

    $origin = origin_for(Contributor::class, '3001');

    expect($origin)->not->toBeNull()
        ->and($origin->source_label)->toBe('naxos.example.it')
        ->and($origin->url)->toBe('https://naxos.example.it/autori/giulia-rossi')
        ->and($origin->source_updated_at?->toDateTimeString())->toBe('2026-08-30 08:00:00');
});

it('records the origin of an imported location', function (): void {
    resolve(LocationUpserter::class)->upsert(new ImportLocationDto(
        name: 'Palazzo Ducale',
        slug: 'palazzo-ducale',
        externalId: 5001,
        sourceType: 'fixture@tenant',
        country: 'Italia',
        originLabel: 'naxos.example.it',
        originUrl: 'https://naxos.example.it/luoghi/palazzo-ducale',
    ));

    $origin = origin_for(Location::class, '5001');

    expect($origin)->not->toBeNull()
        ->and($origin->source_label)->toBe('naxos.example.it')
        ->and($origin->url)->toBe('https://naxos.example.it/luoghi/palazzo-ducale')
        ->and($origin->fingerprint)->not->toBeNull();
});

it('leaves the source timestamp empty when the source sends something unreadable', function (): void {
    resolve(TagUpserter::class)->upsert(new ImportTagDto(
        externalId: 4002,
        name: 'Cronaca',
        slug: 'cronaca',
        type: null,
        orderColumn: 0,
        createdAt: null,
        updatedAt: 'not a date',
        deletedAt: null,
        sourceType: 'fixture@tenant',
    ));

    $origin = origin_for(Tag::class, '4002');

    expect($origin)->not->toBeNull()
        ->and($origin->source_updated_at)->toBeNull()
        ->and($origin->source_label)->toBeNull();
});

it('fingerprints the origin of an imported content', function (): void {
    resolve(ImportIdMap::class)->reset();

    $graph = buildImportGraphFromFixture();
    $content_id = resolve(ImportPipeline::class)->import($graph);

    $origin = origin_for(Content::class, (string) $graph->content->externalId);

    expect($origin)->not->toBeNull()
        ->and((int) $origin->referable_id)->toBe($content_id)
        ->and($origin->fingerprint)->toBe(ImportFingerprint::of($graph->content));
});
