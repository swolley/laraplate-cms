<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Tag;
use Modules\Cms\Models\Translations\ContentTranslation;
use Modules\Cms\Models\Translations\TagTranslation;
use Modules\Cms\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! Schema::hasTable('contents_translations') || ! Schema::hasTable('tags_translations')) {
        $this->markTestSkipped('Translation tables required.');
    }

    setupCmsEntities();
});

it('content translation belongs to content', function (): void {
    $content = Content::factory()->create();
    $translation = ContentTranslation::query()->create([
        'content_id' => $content->id,
        'locale' => config('app.locale'),
        'title' => 'T',
        'slug' => 't',
        'components' => [],
    ]);

    expect($translation->content)->toBeInstanceOf(Content::class)
        ->and($translation->content->is($content))->toBeTrue();
});

it('tag translation belongs to tag', function (): void {
    $tag = Tag::factory()->create();
    $translation = TagTranslation::query()->create([
        'tag_id' => $tag->id,
        'locale' => config('app.locale'),
        'name' => 'Name',
        'slug' => 'name',
    ]);

    expect($translation->tag)->toBeInstanceOf(Tag::class)
        ->and($translation->tag->is($tag))->toBeTrue();
});
