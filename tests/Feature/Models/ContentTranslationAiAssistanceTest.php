<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\AiAssistance;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Translations\ContentTranslation;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! Schema::hasColumn(CMSTables::ContentsTranslations->value, 'ai_assistance')) {
        $this->markTestSkipped('ai_assistance column not migrated yet.');
    }

    setupCMSEntities();
});

it('defaults ai assistance to none', function (): void {
    $content = Content::factory()->create();
    $locale = (string) config('app.locale');

    $content->setTranslation($locale, [
        'title' => 'Test',
        'slug' => 'test',
        'components' => [],
    ]);
    $content->save();

    $translation = ContentTranslation::query()
        ->where('content_id', $content->id)
        ->where('locale', $locale)
        ->first();

    expect($translation)->toBeInstanceOf(ContentTranslation::class)
        ->and($translation->ai_assistance)->toBe(AiAssistance::None);
});

it('casts ai assistance enum on translation', function (): void {
    $content = Content::factory()->create();
    $locale = (string) config('app.locale');

    $content->setTranslation($locale, [
        'title' => 'Test',
        'slug' => 'test',
        'components' => [],
        'ai_assistance' => AiAssistance::Translated,
    ]);
    $content->save();

    $translation = ContentTranslation::query()
        ->where('content_id', $content->id)
        ->where('locale', $locale)
        ->first();

    expect($translation->ai_assistance)->toBe(AiAssistance::Translated);
});

it('filters translations by ai assistance', function (): void {
    $content = Content::factory()->create();
    $locale = (string) config('app.locale');

    $content->setTranslation($locale, [
        'title' => 'AI article',
        'slug' => 'ai-article',
        'components' => [],
        'ai_assistance' => AiAssistance::Generated,
    ]);
    $content->save();

    $matches = ContentTranslation::query()
        ->where('ai_assistance', AiAssistance::Generated->value)
        ->where('content_id', $content->id)
        ->count();

    expect($matches)->toBe(1);
});
