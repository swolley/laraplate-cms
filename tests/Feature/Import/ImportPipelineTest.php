<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Import\Dto\ImportGraphDto;
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

it('merges per-locale translations onto a single content and maps the whole family', function (): void {
    $base_graph = buildImportGraphFromFixture();

    $base_locale = (string) config('cms.import.locale', config('app.locale'));
    $target_locale = $base_locale === 'en' ? 'it' : 'en';
    $family_external_id = $base_graph->content->externalId + 1;

    $content_dto = $base_graph->content->withTranslations(
        [
            $target_locale => [
                'title' => 'Translated article',
                'slug' => 'translated-article',
                'components' => [],
            ],
        ],
        familyExternalIds: [$base_graph->content->externalId, $family_external_id],
    );

    $graph = new ImportGraphDto(
        content: $content_dto,
        categories: $base_graph->categories,
        contributors: $base_graph->contributors,
        tags: $base_graph->tags,
    );

    $pipeline = resolve(ImportPipeline::class);
    $content_id = $pipeline->import($graph);

    $content = Content::query()->withoutGlobalScopes()->findOrFail($content_id);

    expect(Content::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and($content->getTranslation($base_locale, false)?->title)->toBe('Sample imported article')
        ->and($content->getTranslation($target_locale, false)?->title)->toBe('Translated article')
        ->and($content->getTranslation($target_locale, false)?->slug)->toBe('translated-article')
        ->and(resolve(ImportIdMap::class)->resolve('contents', $family_external_id))->toBe($content_id);
});
