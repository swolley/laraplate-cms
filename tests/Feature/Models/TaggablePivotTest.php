<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Pivot\Taggable;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCMSEntities();
});

test('tagging a content yields a Taggable morph pivot carrying timestamps', function (): void {
    $content = Content::factory()->create();
    $tag = Tag::factory()->create();

    $content->tags()->attach($tag->id);

    $pivot = $content->tags()->first()->pivot;
    expect($pivot)->toBeInstanceOf(Taggable::class);
    expect($pivot->created_at)->not->toBeNull();
});

test('the inverse Tag->contents relation exposes the same Taggable pivot', function (): void {
    $content = Content::factory()->create();
    $tag = Tag::factory()->create();

    $tag->contents()->attach($content->id);

    $pivot = $tag->contents()->first()->pivot;
    expect($pivot)->toBeInstanceOf(Taggable::class);
    expect($pivot->created_at)->not->toBeNull();
});
