<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Import\Enums\ImportSourceFormat;
use Modules\Core\Import\Support\ImportRunner;
use Modules\Core\Models\ImportSession;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config(['scout.driver' => 'null', 'app.locale' => 'en']);
    Storage::fake('local');
    setupCMSEntities([EntityType::Contents, EntityType::Contributors, EntityType::Categories]);
});

/**
 * @param  array<string, string>  $mapping
 */
function contentImportSession(string $entityKey, string $csv, array $mapping): ImportSession
{
    Storage::disk('local')->put('content.csv', $csv);

    return ImportSession::factory()->create([
        'entity_key' => $entityKey,
        'source_format' => ImportSourceFormat::Csv,
        'file_disk' => 'local',
        'file_path' => 'content.csv',
        'original_filename' => 'content.csv',
        'mapping' => $mapping,
    ]);
}

test('the content importer attaches relations by natural key, creating tags but failing on an unknown category', function (): void {
    // Seed the curated relations (contributors, categories) the content will attach.
    app(ImportRunner::class)->process(contentImportSession('cms.contributor', "name\nAda Lovelace\n", ['name' => 'name']));
    app(ImportRunner::class)->process(contentImportSession('cms.category', "name\nNews\n", ['name' => 'name']));

    $session = contentImportSession('cms.content',
        "title,tags,categories,contributors\n"
        . "Hello World,\"sport, music\",news,Ada Lovelace\n"
        . "Orphan,foo,ghost-cat,Ada Lovelace\n",
        ['title' => 'title', 'tags' => 'tags', 'categories' => 'categories', 'contributors' => 'contributors'],
    );

    app(ImportRunner::class)->process($session);
    $session->refresh();

    expect($session->created_rows)->toBe(1)
        ->and($session->failed_rows)->toBe(1)
        ->and($session->rowErrors()->first()->errors)->toHaveKey('categories')
        ->and(Content::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and(Tag::query()->count())->toBe(2); // sport + music created on the fly

    $content = Content::query()->withoutGlobalScopes()
        ->whereHas('translations', fn ($q) => $q->where('slug', 'hello-world'))->sole();

    expect($content->tags()->count())->toBe(2)
        ->and($content->categories()->count())->toBe(1)
        ->and($content->categories()->first()->slug)->toBe('news')
        ->and($content->contributors()->count())->toBe(1)
        ->and($content->contributors()->first()->name)->toBe('Ada Lovelace');
});

test('re-importing a content updates it in place and re-syncs its relations', function (): void {
    app(ImportRunner::class)->process(contentImportSession('cms.category', "name\nNews\n", ['name' => 'name']));

    app(ImportRunner::class)->process(contentImportSession('cms.content',
        "title,tags,categories\nHello World,sport,news\n",
        ['title' => 'title', 'tags' => 'tags', 'categories' => 'categories'],
    ));

    $second = contentImportSession('cms.content',
        "title,tags,categories\nHello World,\"sport, music\",news\n",
        ['title' => 'title', 'tags' => 'tags', 'categories' => 'categories'],
    );
    app(ImportRunner::class)->process($second);

    expect($second->fresh()->updated_rows)->toBe(1)
        ->and(Content::query()->withoutGlobalScopes()->count())->toBe(1);

    $content = Content::query()->withoutGlobalScopes()
        ->whereHas('translations', fn ($q) => $q->where('slug', 'hello-world'))->sole();

    expect($content->tags()->count())->toBe(2); // sync grew from {sport} to {sport, music}
});
