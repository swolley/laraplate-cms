<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Import\Pipeline\ImportPipeline;
use Modules\CMS\Import\Support\ImportIdMap;
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
        $this->markTestSkipped('CMS import tests require full schema.');
    }

    setupCMSEntities();
    DynamicContentsService::reset();
    resolve(ImportIdMap::class)->reset();
});

it('registers a record origin for every imported entity', function (): void {
    $graph = buildImportGraphFromFixture();
    $content_id = resolve(ImportPipeline::class)->import($graph);

    $content_origin = RecordOrigin::query()
        ->where('referable_type', Content::class)
        ->where('source_key', 'fixture')
        ->where('external_id', '1001')
        ->first();

    expect($content_origin)->not->toBeNull()
        ->and((int) $content_origin->referable_id)->toBe($content_id)
        ->and(RecordOrigin::query()->where('referable_type', Category::class)->where('external_id', '2001')->exists())->toBeTrue()
        ->and(RecordOrigin::query()->where('referable_type', Contributor::class)->where('external_id', '3001')->exists())->toBeTrue()
        ->and(RecordOrigin::query()->where('referable_type', Tag::class)->where('external_id', '4001')->exists())->toBeTrue();
});

it('does not leak import bookkeeping into shared_components', function (): void {
    $graph = buildImportGraphFromFixture();
    $content_id = resolve(ImportPipeline::class)->import($graph);

    $shared = (array) (Content::query()->withoutGlobalScopes()->findOrFail($content_id)->shared_components ?? []);

    expect($shared)->not->toHaveKeys(['external_id', 'import_source', 'source_kind', 'naxos_id', 'naxos_kind']);
});

it('keeps a single origin row per entity across repeated imports', function (): void {
    $graph = buildImportGraphFromFixture();
    $pipeline = resolve(ImportPipeline::class);

    $pipeline->import($graph);
    resolve(ImportIdMap::class)->reset();
    $pipeline->import($graph);

    $rows = RecordOrigin::query()
        ->where('referable_type', Content::class)
        ->where('source_key', 'fixture')
        ->where('external_id', '1001')
        ->count();

    expect($rows)->toBe(1)
        ->and(Content::query()->withoutGlobalScopes()->count())->toBe(1);
});
