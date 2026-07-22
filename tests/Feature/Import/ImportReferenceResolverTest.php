<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Import\Pipeline\ImportPipeline;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportIdMap;
use Modules\CMS\Import\Support\ImportReferenceResolver;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\RecordOrigin;
use Modules\Core\Services\DynamicContentsService;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config(['scout.driver' => 'null']);

    if (! Schema::hasTable(CMSTables::Contents->value)) {
        $this->markTestSkipped('CMS import reference tests require full schema.');
    }

    setupCMSEntities();
    DynamicContentsService::reset();
    resolve(ImportIdMap::class)->reset();
});

it('resolves imported records through the in-memory map before the origin registry', function (): void {
    $resolver = resolve(ImportReferenceResolver::class);

    $resolver->remember('tags', 4001, 99);

    expect($resolver->resolve('tags', Tag::class, 4001, 'fixture'))->toBe(99);
});

it('resolves imported records from core_record_origins when the map is empty', function (): void {
    $graph = buildImportGraphFromFixture();
    resolve(ImportPipeline::class)->import($graph);
    resolve(ImportIdMap::class)->reset();

    $resolver = resolve(ImportReferenceResolver::class);

    expect($resolver->resolve('contents', Content::class, 1001, 'fixture'))->toBeInt()
        ->and($resolver->resolve('categories', Category::class, 2001, 'fixture'))->toBeInt()
        ->and($resolver->resolve('contributors', Contributor::class, 3001, 'fixture'))->toBeInt()
        ->and($resolver->resolve('tags', Tag::class, 4001, 'fixture'))->toBeInt();
});

it('does not match unrelated business slugs before the origin registry', function (): void {
    $graph = buildImportGraphFromFixture();
    resolve(ImportPipeline::class)->import($graph);
    resolve(ImportIdMap::class)->reset();

    $existing_tag_id = resolve(ImportReferenceResolver::class)->resolve('tags', Tag::class, 4001, 'fixture');

    $unrelated_match = resolve(ImportReferenceResolver::class)->resolve(
        'tags',
        Tag::class,
        9999,
        'fixture',
    );

    expect($unrelated_match)->toBeNull()
        ->and(RecordOrigin::query()->where('referable_type', Tag::class)->where('external_id', '9999')->exists())->toBeFalse()
        ->and($existing_tag_id)->toBeInt();
});

it('builds deterministic import slugs from source type and external id', function (): void {
    $locator = resolve(ExternalReferenceLocator::class);

    expect($locator->importSlug(1001, 'naxos'))->toBe('import-naxos-1001')
        ->and($locator->importSlug(42, 'cms_default'))->toBe('import-cms_default-42');
});

it('detects when a content origin is already registered', function (): void {
    $graph = buildImportGraphFromFixture();
    resolve(ImportPipeline::class)->import($graph);

    $locator = resolve(ExternalReferenceLocator::class);

    expect($locator->hasImportedRecord(Content::class, 1001, 'fixture'))->toBeTrue()
        ->and($locator->hasImportedRecord(Content::class, 9999, 'fixture'))->toBeFalse();
});

it('rejects a locator target whose class differs from the declared class before querying', function (): void {
    $locator = resolve(ExternalReferenceLocator::class);

    expect(fn () => $locator->findImportedRecordId(
        Content::class,
        1001,
        'fixture',
        new Contributor,
    ))->toThrow(LogicException::class, 'Declared import model');
});

it('isolates in-memory ids by connection and source identity', function (): void {
    $map = resolve(ImportIdMap::class);
    $map->remember('contents', 10, 101, 'sqlite', 'source-a');
    $map->remember('contents', 10, 202, 'affinity', 'source-a');
    $map->remember('contents', 10, 303, 'sqlite', 'source-b');

    expect($map->resolve('contents', 10, 'sqlite', 'source-a'))->toBe(101)
        ->and($map->resolve('contents', 10, 'affinity', 'source-a'))->toBe(202)
        ->and($map->resolve('contents', 10, 'sqlite', 'source-b'))->toBe(303);

    expect(fn (): ?int => $map->resolve('contents', 10))
        ->toThrow(LogicException::class, 'ambiguous without connection and source context');
});
