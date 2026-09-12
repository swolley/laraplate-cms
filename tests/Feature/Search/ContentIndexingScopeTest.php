<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Entity;
use Modules\CMS\Models\Pivot\Presettable;
use Modules\CMS\Models\Preset;
use Modules\CMS\Models\Translations\ContentTranslation;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Create a bare Content row (no translations yet) against the default entity/preset,
 * mirroring the setup used across CMS search feature tests.
 */
function createSearchableContentShell(): Content
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

    return Content::query()->create([
        'entity_id' => $entity->id,
        'presettable_id' => $presettable->id,
        'valid_from' => now(),
    ]);
}

it('indexes mono-language content that the runtime LocaleScope would otherwise hide', function (): void {
    // phpunit.xml forces APP_LOCALE=en for the test environment (the app's own
    // .env defaults to 'it'). Fixtures below key off the ACTUAL default locale,
    // not an assumption: the mono-language content has no translation in that
    // default locale, so it must be excluded from LocaleScope-filtered runtime
    // queries but still picked up by the unscoped Scout import query.
    $defaultLocale = config('app.locale');
    $otherLocale = $defaultLocale === 'it' ? 'en' : 'it';

    $monoLanguage = createSearchableContentShell();

    ContentTranslation::query()->create([
        'content_id' => $monoLanguage->id,
        'locale' => $otherLocale,
        'title' => 'Mono-language title',
        'slug' => Str::slug('Mono-language title'),
        'components' => [],
    ]);

    $bilingual = createSearchableContentShell();

    ContentTranslation::query()->create([
        'content_id' => $bilingual->id,
        'locale' => $defaultLocale,
        'title' => 'Default locale title',
        'slug' => Str::slug('Default locale title'),
        'components' => [],
    ]);

    ContentTranslation::query()->create([
        'content_id' => $bilingual->id,
        'locale' => $otherLocale,
        'title' => 'Other locale title',
        'slug' => Str::slug('Other locale title'),
        'components' => [],
    ]);

    $importQuery = (new Content)->makeAllSearchableUsing(Content::query());

    expect($importQuery->pluck('id'))->toContain($monoLanguage->id)
        ->and($importQuery->pluck('id'))->toContain($bilingual->id)
        ->and(Content::query()->pluck('id'))->not->toContain($monoLanguage->id)
        ->and(Content::query()->pluck('id'))->toContain($bilingual->id);
});
