<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Tag;
use Modules\CMS\Models\Translations\ContentTranslation;
use Modules\CMS\Models\Translations\TagTranslation;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! Schema::hasTable(CMSTables::ContentsTranslations->value) || ! Schema::hasTable(CMSTables::TagsTranslations->value)) {
        $this->markTestSkipped('Translation tables required.');
    }

    setupCMSEntities();
});

it('content translation belongs to content', function (): void {
    $content = Content::factory()->create([
        'valid_from' => now()->subDay(),
        'valid_to' => null,
    ]);
    $translation = $content->translations()
        ->where('locale', config('app.locale'))
        ->firstOrFail();

    expect($translation)->toBeInstanceOf(ContentTranslation::class)
        ->and($translation->content->is($content))->toBeTrue();
});

it('tag translation belongs to tag', function (): void {
    $tag = Tag::factory()->create();
    $translation = $tag->translations()
        ->where('locale', config('app.locale'))
        ->firstOrFail();

    expect($translation)->toBeInstanceOf(TagTranslation::class)
        ->and($translation->tag->is($tag))->toBeTrue();
});
