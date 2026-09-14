<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\AI\Jobs\GenerateEmbeddingsJob;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Enums\AiAssistance;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Entity;
use Modules\CMS\Models\Pivot\Presettable;
use Modules\CMS\Models\Preset;
use Modules\CMS\Models\Translations\ContentTranslation;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Events\ModelRequiresIndexing;

uses(TestCase::class, RefreshDatabase::class);

// GenerateEmbeddingsJob is dispatched synchronously under the test suite's
// QUEUE_CONNECTION=sync: fake the queue for every test in this file up front so
// that setup helpers (which create/update ContentTranslation rows and thus
// trigger the observer) never execute the job for real. Tests that need to
// isolate the action under test re-fake (discarding the setup noise) right
// before that action, mirroring LocationObserverTest's convention.
//
// The observer only dispatches when Content::isEmbeddable() is true, which
// requires search.vector_search.enabled (off by default, mirroring
// HandleModelIndexingListenerTest's own setup) — enable it here so the
// saved()-path tests exercise real behavior instead of the disabled no-op.
beforeEach(function (): void {
    Queue::fake();
    Config::set('search.vector_search.enabled', true);
});

/**
 * Build a Content with exactly two translations (it + en), bypassing the factory's
 * randomized extra-locale duplication so translation identity is deterministic
 * (mirrors createBilingualSearchableContent() in ContentSearchableArrayTest.php).
 */
function createReembedTestContent(string $itTitle, string $enTitle): Content
{
    setupCMSEntities([EntityType::Contents]);

    $entity = Entity::query()->where('name', 'contents')->firstOrFail();
    $preset = Preset::query()->where('entity_id', $entity->id)->where('name', 'default')->firstOrFail();
    $presettable = Presettable::query()
        ->where('entity_id', $entity->id)
        ->where('preset_id', $preset->id)
        ->whereNull('deleted_at')
        ->latest('version')
        ->firstOrFail();

    $content = Content::query()->create([
        'entity_id' => $entity->id,
        'presettable_id' => $presettable->id,
        'valid_from' => now(),
    ]);

    ContentTranslation::query()->create([
        'content_id' => $content->id,
        'locale' => 'it',
        'title' => $itTitle,
        'slug' => Str::slug($itTitle),
        'components' => [],
    ]);

    ContentTranslation::query()->create([
        'content_id' => $content->id,
        'locale' => 'en',
        'title' => $enTitle,
        'slug' => Str::slug($enTitle),
        'components' => [],
    ]);

    return $content->fresh(['translations']);
}

/**
 * Name of the sole (textual) dynamic field attached to the default "contents"
 * preset by setupCMSEntities(), so a components-only change can be exercised
 * without depending on a hardcoded field name.
 */
function reembedTestTextualFieldName(): string
{
    $entity = Entity::query()->where('name', 'contents')->firstOrFail();
    $preset = Preset::query()->where('entity_id', $entity->id)->where('name', 'default')->firstOrFail();

    return $preset->fields()->firstOrFail()->name;
}

/**
 * Pull the private constructor-promoted $model/$locale out of a dispatched
 * GenerateEmbeddingsJob (it has no public accessors) so tests can assert what
 * it was scoped to.
 *
 * @return array{0: \Illuminate\Database\Eloquent\Model, 1: ?string}
 */
function reembedJobArgs(GenerateEmbeddingsJob $job): array
{
    $reflection = new ReflectionClass($job);

    $model_property = $reflection->getProperty('model');
    $model_property->setAccessible(true);

    $locale_property = $reflection->getProperty('locale');
    $locale_property->setAccessible(true);

    return [$model_property->getValue($job), $locale_property->getValue($job)];
}

// ─────────────────────────────────────────────────────────────────────────────
// saved(): new translation always re-embeds (wasRecentlyCreated)
// ─────────────────────────────────────────────────────────────────────────────

it('dispatches GenerateEmbeddingsJob scoped to the new locale when a translation is created', function (): void {
    setupCMSEntities([EntityType::Contents]);

    $entity = Entity::query()->where('name', 'contents')->firstOrFail();
    $preset = Preset::query()->where('entity_id', $entity->id)->where('name', 'default')->firstOrFail();
    $presettable = Presettable::query()
        ->where('entity_id', $entity->id)
        ->where('preset_id', $preset->id)
        ->whereNull('deleted_at')
        ->latest('version')
        ->firstOrFail();

    $content = Content::query()->create([
        'entity_id' => $entity->id,
        'presettable_id' => $presettable->id,
        'valid_from' => now(),
    ]);

    Queue::fake();

    ContentTranslation::query()->create([
        'content_id' => $content->id,
        'locale' => 'it',
        'title' => 'Titolo iniziale',
        'slug' => 'titolo-iniziale',
        'components' => [],
    ]);

    Queue::assertPushed(GenerateEmbeddingsJob::class, 1);
    Queue::assertPushed(GenerateEmbeddingsJob::class, function (GenerateEmbeddingsJob $job) use ($content): bool {
        [$model, $locale] = reembedJobArgs($job);

        return $model->is($content) && $locale === 'it';
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// saved(): title change re-embeds only that locale
// ─────────────────────────────────────────────────────────────────────────────

it('dispatches GenerateEmbeddingsJob scoped to only the changed locale when a translation title changes', function (): void {
    $content = createReembedTestContent('Titolo di prova', 'Test title');

    Queue::fake();

    $content->translations()->where('locale', 'it')->first()->update(['title' => 'Titolo aggiornato']);

    Queue::assertPushed(GenerateEmbeddingsJob::class, 1);
    Queue::assertPushed(GenerateEmbeddingsJob::class, function (GenerateEmbeddingsJob $job) use ($content): bool {
        [$model, $locale] = reembedJobArgs($job);

        return $model->is($content) && $locale === 'it';
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// saved(): derived-attribute trap — a components-only change (which feeds the
// derived `textual_only` embed field) must still trigger a re-embed, even
// though `getChanges()` never lists "textual_only" itself.
// ─────────────────────────────────────────────────────────────────────────────

it('dispatches GenerateEmbeddingsJob when only components change (feeds the derived textual_only embed field)', function (): void {
    $content = createReembedTestContent('Titolo di prova', 'Test title');
    $field_name = reembedTestTextualFieldName();

    Queue::fake();

    $translation = $content->translations()->where('locale', 'it')->first();
    $translation->update(['components' => [$field_name => 'Nuovo contenuto testuale']]);

    expect($translation->wasChanged('components'))->toBeTrue()
        ->and($translation->wasChanged('title'))->toBeFalse();

    Queue::assertPushed(GenerateEmbeddingsJob::class, 1);
    Queue::assertPushed(GenerateEmbeddingsJob::class, function (GenerateEmbeddingsJob $job) use ($content): bool {
        [$model, $locale] = reembedJobArgs($job);

        return $model->is($content) && $locale === 'it';
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// saved(): unrelated field changes must NOT dispatch a re-embed
// ─────────────────────────────────────────────────────────────────────────────

it('does not dispatch GenerateEmbeddingsJob when only a non-embeddable field changes', function (): void {
    $content = createReembedTestContent('Titolo di prova', 'Test title');

    Queue::fake();

    $translation = $content->translations()->where('locale', 'it')->first();
    $translation->update(['ai_assistance' => AiAssistance::Edited]);

    Queue::assertNotPushed(GenerateEmbeddingsJob::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// saved(): the Content::isEmbeddable() gate — no dispatch when vector search
// itself is disabled, regardless of what changed. Without this, every
// ContentTranslation save anywhere (e.g. Content::factory()->create() across
// the wider CMS suite) would enqueue a real embedding call under the test
// suite's synchronous queue connection.
// ─────────────────────────────────────────────────────────────────────────────

it('does not dispatch GenerateEmbeddingsJob when vector search is disabled, even on a title change', function (): void {
    $content = createReembedTestContent('Titolo di prova', 'Test title');

    Config::set('search.vector_search.enabled', false);
    Queue::fake();

    $content->translations()->where('locale', 'it')->first()->update(['title' => 'Titolo aggiornato']);

    Queue::assertNotPushed(GenerateEmbeddingsJob::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// deleted(): drops that locale's embeddings only, and reindexes the parent
// ─────────────────────────────────────────────────────────────────────────────

it('deletes only the deleted translation locale embeddings and reindexes the parent content', function (): void {
    $content = createReembedTestContent('Titolo di prova', 'Test title');

    $content->embeddings()->create(['embedding' => [0.1, 0.2], 'locale' => 'it', 'model_key' => 'test-model']);
    $content->embeddings()->create(['embedding' => [0.3, 0.4], 'locale' => 'en', 'model_key' => 'test-model']);

    Event::fake(ModelRequiresIndexing::class);

    $content->translations()->where('locale', 'it')->first()->delete();

    expect($content->embeddings()->forLocale('it')->count())->toBe(0)
        ->and($content->embeddings()->forLocale('en')->count())->toBe(1);

    Event::assertDispatched(ModelRequiresIndexing::class, fn (ModelRequiresIndexing $event): bool => $event->model->is($content));
});

it('does not dispatch a re-embed job when a translation is deleted', function (): void {
    $content = createReembedTestContent('Titolo di prova', 'Test title');

    Queue::fake();
    Event::fake(ModelRequiresIndexing::class);

    $content->translations()->where('locale', 'it')->first()->delete();

    Queue::assertNotPushed(GenerateEmbeddingsJob::class);
});
