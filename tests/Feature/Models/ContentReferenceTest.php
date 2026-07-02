<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\ContentReference;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! Schema::hasTable(CMSTables::ContentsReferences->value)) {
        $this->markTestSkipped('cms_contents_references table not migrated.');
    }

    setupCMSEntities();
    $this->content = Content::factory()->create();
});

it('creates references through content relation', function (): void {
    $reference = $this->content->references()->create([
        'label' => 'Wikipedia',
        'url' => 'https://en.wikipedia.org/wiki/Example',
    ]);

    expect($reference)->toBeInstanceOf(ContentReference::class)
        ->and($this->content->references)->toHaveCount(1)
        ->and($this->content->references->first()->label)->toBe('Wikipedia');
});

it('orders references per content independently', function (): void {
    $otherContent = Content::factory()->create();

    $first = $this->content->references()->create(['label' => 'First', 'order_column' => 1]);
    $second = $this->content->references()->create(['label' => 'Second', 'order_column' => 2]);
    $otherContent->references()->create(['label' => 'Other', 'order_column' => 1]);

    $ordered = ContentReference::query()
        ->where('content_id', $this->content->id)
        ->ordered()
        ->pluck('label')
        ->all();

    expect($ordered)->toBe(['First', 'Second']);
});

it('cascades delete when content is deleted', function (): void {
    $reference = ContentReference::factory()->create(['content_id' => $this->content->id]);

    $this->content->forceDelete();

    expect(ContentReference::query()->whereKey($reference->id)->exists())->toBeFalse();
});

it('soft deletes a reference', function (): void {
    $reference = ContentReference::factory()->create(['content_id' => $this->content->id]);

    $reference->delete();

    expect($reference->trashed())->toBeTrue()
        ->and(ContentReference::query()->whereKey($reference->id)->exists())->toBeFalse()
        ->and(ContentReference::withTrashed()->whereKey($reference->id)->exists())->toBeTrue();
});
