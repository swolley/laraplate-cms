<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Tag;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tag = Tag::factory()->create();
});

it('can be created with factory', function (): void {
    expect($this->tag)->toBeInstanceOf(Tag::class);
    expect($this->tag->id)->not->toBeNull();
});

it('has fillable attributes', function (): void {
    $tagData = [
        'name' => 'Laravel',
        'slug' => 'laravel',
        'type' => 'technology',
        'order_column' => 1,
    ];

    $tag = Tag::create($tagData);

    expectModelAttributes($tag, [
        'name' => 'Laravel',
        'slug' => 'laravel',
        'type' => 'technology',
        'order_column' => 1,
    ]);
});

it('has hidden attributes', function (): void {
    $tag = Tag::factory()->create();
    $tagArray = $tag->toArray();

    expect($tagArray)->not->toHaveKey('order_column');
    expect($tagArray)->not->toHaveKey('created_at');
    expect($tagArray)->not->toHaveKey('updated_at');
});

it('belongs to many contents', function (): void {
    $content1 = Content::factory()->create(['title' => 'Article 1']);
    $content2 = Content::factory()->create(['title' => 'Article 2']);

    $this->tag->contents()->attach([$content1->id, $content2->id]);

    expect($this->tag->contents)->toHaveCount(2);
    expect($this->tag->contents->pluck('title')->toArray())->toContain('Article 1', 'Article 2');
});

it('has slug trait', function (): void {
    expect($this->tag)->toHaveMethod('generateSlug');
    expect($this->tag)->toHaveMethod('getSlug');
});

it('has path trait', function (): void {
    expect($this->tag)->toHaveMethod('getPath');
    expect($this->tag)->toHaveMethod('setPath');
});

it('has sortable trait', function (): void {
    expect($this->tag)->toHaveMethod('moveOrder');
    expect($this->tag)->toHaveMethod('getOrder');
});

it('has soft deletes trait', function (): void {
    $this->tag->delete();

    expect($this->tag->trashed())->toBeTrue();
    expect(Tag::withTrashed()->find($this->tag->id))->not->toBeNull();
});

it('has validations trait', function (): void {
    expect($this->tag)->toHaveMethod('getRules');
});

it('can find or create tags', function (): void {
    $tag = Tag::findOrCreate('Laravel');

    expect($tag)->toBeInstanceOf(Tag::class);
    expect($tag->name)->toBe('Laravel');

    // Should return the same tag if called again
    $sameTag = Tag::findOrCreate('Laravel');
    expect($sameTag->id)->toBe($tag->id);
});

it('can find or create multiple tags', function (): void {
    $tags = Tag::findOrCreate(['Laravel', 'PHP', 'Web Development']);

    expect($tags)->toHaveCount(3);
    expect($tags->pluck('name')->toArray())->toContain('Laravel', 'PHP', 'Web Development');
});

it('can be created with specific attributes', function (): void {
    $tagData = [
        'name' => 'JavaScript',
        'slug' => 'javascript',
        'type' => 'programming',
        'order_column' => 2,
    ];

    $tag = Tag::create($tagData);

    expectModelAttributes($tag, [
        'name' => 'JavaScript',
        'slug' => 'javascript',
        'type' => 'programming',
        'order_column' => 2,
    ]);
});

it('can be found by name', function (): void {
    $tag = Tag::factory()->create(['name' => 'Unique Tag']);

    $foundTag = Tag::where('name', 'Unique Tag')->first();

    expect($foundTag->id)->toBe($tag->id);
});

it('can be found by slug', function (): void {
    $tag = Tag::factory()->create(['slug' => 'unique-slug']);

    $foundTag = Tag::where('slug', 'unique-slug')->first();

    expect($foundTag->id)->toBe($tag->id);
});

it('can be found by type', function (): void {
    $tag = Tag::factory()->create(['type' => 'technology']);

    $foundTag = Tag::where('type', 'technology')->first();

    expect($foundTag->id)->toBe($tag->id);
});

it('has proper timestamps', function (): void {
    $tag = Tag::factory()->create();

    expect($tag->created_at)->toBeInstanceOf(Carbon\Carbon::class);
    expect($tag->updated_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('can be serialized to array', function (): void {
    $tag = Tag::factory()->create([
        'name' => 'Test Tag',
        'slug' => 'test-tag',
        'type' => 'test',
    ]);
    $tagArray = $tag->toArray();

    expect($tagArray)->toHaveKey('id');
    expect($tagArray)->toHaveKey('name');
    expect($tagArray)->toHaveKey('slug');
    expect($tagArray)->toHaveKey('type');
    expect($tagArray['name'])->toBe('Test Tag');
    expect($tagArray['slug'])->toBe('test-tag');
    expect($tagArray['type'])->toBe('test');
});

it('can be restored after soft delete', function (): void {
    $tag = Tag::factory()->create();
    $tag->delete();

    expect($tag->trashed())->toBeTrue();

    $tag->restore();

    expect($tag->trashed())->toBeFalse();
});

it('can be permanently deleted', function (): void {
    $tag = Tag::factory()->create();
    $tagId = $tag->id;

    $tag->forceDelete();

    expect(Tag::withTrashed()->find($tagId))->toBeNull();
});

it('can be sorted by order column', function (): void {
    $tag1 = Tag::factory()->create(['order_column' => 2]);
    $tag2 = Tag::factory()->create(['order_column' => 1]);
    $tag3 = Tag::factory()->create(['order_column' => 3]);

    $sortedTags = Tag::ordered()->get();

    expect($sortedTags->first()->id)->toBe($tag2->id);
    expect($sortedTags->last()->id)->toBe($tag3->id);
});

it('can be moved in order', function (): void {
    $tag1 = Tag::factory()->create(['order_column' => 1]);
    $tag2 = Tag::factory()->create(['order_column' => 2]);
    $tag3 = Tag::factory()->create(['order_column' => 3]);

    $tag1->moveOrder(3);

    expect($tag1->fresh()->order_column)->toBe(3);
    expect($tag2->fresh()->order_column)->toBe(1);
    expect($tag3->fresh()->order_column)->toBe(2);
});
