<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Import\Dto\ImportContentDto;
use Modules\CMS\Import\Dto\ImportGraphDto;
use Modules\CMS\Import\Dto\ImportRelatedContentDto;
use Modules\CMS\Import\Pipeline\ImportPipeline;
use Modules\CMS\Import\Support\ImportIdMap;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
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

it('logs each imported content with its origin url', function (): void {
    config(['scout.driver' => 'null']);
    Log::spy();

    $base_graph = buildImportGraphFromFixture();
    $graph = new ImportGraphDto(
        content: $base_graph->content->withOrigin('Naxos', 'https://example.test/sample-imported-article'),
        categories: $base_graph->categories,
        contributors: $base_graph->contributors,
        tags: $base_graph->tags,
    );

    resolve(ImportPipeline::class)->import($graph);

    Log::shouldHaveReceived('info')
        ->with('imported new content from original url https://example.test/sample-imported-article')
        ->once();
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

it('rejects a mixed connection graph before writing', function (): void {
    config([
        'database.connections.affinity' => [
            ...config('database.connections.sqlite'),
            'database' => ':memory:',
        ],
    ]);
    DB::purge('affinity');

    $content = (new Content)->setConnection('affinity');
    $content_count = Content::query()->withoutGlobalScopes()->count();
    $writes = [];
    DB::listen(static function ($query) use (&$writes): void {
        if (preg_match('/^\s*(insert|update|delete)/i', $query->sql) === 1) {
            $writes[] = $query->sql;
        }
    });

    expect(fn (): int => resolve(ImportPipeline::class)->import(buildImportGraphFromFixture(), $content))
        ->toThrow(LogicException::class, 'Import model [Modules\CMS\Models\Entity] resolves connection [sqlite], expected root [Modules\CMS\Models\Content] connection [affinity].')
        ->and(Content::query()->withoutGlobalScopes()->count())->toBe($content_count)
        ->and($writes)->toBe([]);
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

it('reuses a soft-deleted tag on re-import instead of failing', function (): void {
    $graph = buildImportGraphFromFixture();
    $pipeline = resolve(ImportPipeline::class);

    $pipeline->import($graph);

    $tag = Tag::query()->firstOrFail();
    $tag->delete();

    // Soft delete is an UPDATE: the row stays but the default global scope hides it.
    expect(Tag::query()->count())->toBe(0)
        ->and(Tag::query()->withoutGlobalScopes()->count())->toBe(1);

    resolve(ImportIdMap::class)->reset();

    // Without withoutGlobalScopes() this second import threw ModelNotFoundException,
    // because the tag id resolved from the origin registry / translation slug pointed
    // to a soft-deleted row excluded by the global scope.
    $content_id = $pipeline->import($graph);
    $content = Content::query()->withoutGlobalScopes()->findOrFail($content_id);

    expect(Tag::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and($content->tags)->toHaveCount(1);
});

it('revives a soft-deleted related record on re-import', function (string $model): void {
    $graph = buildImportGraphFromFixture();
    $pipeline = resolve(ImportPipeline::class);

    $pipeline->import($graph);

    /** @var class-string<Modules\Core\Overrides\Model> $model */
    $record = $model::query()->firstOrFail();
    $record->delete();

    expect($model::query()->count())->toBe(0)
        ->and($model::query()->withoutGlobalScopes()->count())->toBe(1);

    resolve(ImportIdMap::class)->reset();

    // Without restore()-on-reimport this second import threw AuthorizationException
    // ("Cannot update a softdeleted model") when saving the revived record.
    $pipeline->import($graph);

    expect($model::query()->count())->toBe(1)
        ->and($model::query()->withoutGlobalScopes()->count())->toBe(1);
})->with([
    // Content is intentionally omitted: cms_contents is missing the optimistic
    // locking "lock_version" column in this schema (needs `php artisan
    // model:lock-refresh` + migrate), so any dirty Content update fails under
    // strict mode regardless of the revive logic. That is a separate setup gap.
    'category' => Category::class,
    'contributor' => Contributor::class,
]);

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

it('imports related contents into cms_relatables within the same graph', function (): void {
    $source_type = 'naxos_api@test-tenant';

    $child_graph = new ImportGraphDto(
        content: new ImportContentDto(
            title: 'Related story',
            slug: 'related-story',
            components: [],
            sharedComponents: [],
            validFrom: now()->toDateTimeString(),
            validTo: null,
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
            externalId: 200,
            externalUuid: null,
            sourceType: $source_type,
        ),
    );

    $parent_graph = new ImportGraphDto(
        content: new ImportContentDto(
            title: 'Parent story',
            slug: 'parent-story',
            components: [],
            sharedComponents: [],
            validFrom: now()->toDateTimeString(),
            validTo: null,
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
            externalId: 100,
            externalUuid: null,
            sourceType: $source_type,
            relatedContents: [
                new ImportRelatedContentDto(
                    externalId: 200,
                    sourceKind: 'story',
                    sourceType: $source_type,
                ),
            ],
        ),
        relatedGraphs: [$child_graph],
    );

    $parent_id = resolve(ImportPipeline::class)->import($parent_graph);
    $parent = Content::query()->withoutGlobalScopes()->findOrFail($parent_id);

    expect($parent->related)->toHaveCount(1)
        ->and($parent->related->first()?->slug)->toBe('related-story');
});
