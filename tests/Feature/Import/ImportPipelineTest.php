<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Import\Pipeline\ImportPipeline;
use Modules\CMS\Import\Support\ImportIdMap;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Services\DynamicContentsService;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! Schema::hasTable(CMSTables::Contents->value)) {
        $this->markTestSkipped('CMS import tests require full schema.');
    }

    setupCMSEntities();
    DynamicContentsService::reset();
    resolve(ImportIdMap::class)->reset();
});

it('imports a graph from fixture dto', function (): void {
    $graph = buildImportGraphFromFixture();
    $pipeline = resolve(ImportPipeline::class);

    $content_id = $pipeline->import($graph);

    $content = Content::query()->withoutGlobalScopes()->findOrFail($content_id);

    expect($content->title)->toBe('Sample imported article')
        ->and($content->slug)->toBe('sample-imported-article')
        ->and($content->categories)->toHaveCount(1)
        ->and($content->contributors)->toHaveCount(1)
        ->and($content->tags)->toHaveCount(1);
});

it('is idempotent when importing the same graph twice', function (): void {
    $graph = buildImportGraphFromFixture();
    $pipeline = resolve(ImportPipeline::class);

    $first_id = $pipeline->import($graph);
    resolve(ImportIdMap::class)->reset();
    $second_id = $pipeline->import($graph);

    expect($second_id)->toBe($first_id)
        ->and(Content::query()->withoutGlobalScopes()->count())->toBe(1);
});
