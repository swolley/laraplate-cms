<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Models\Author;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Location;
use Modules\Cms\Models\Tag;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->content = Content::factory()->create();
});

it('can be created with factory', function (): void {
    expect($this->content)->toBeInstanceOf(Content::class);
    expect($this->content->id)->not->toBeNull();
});

it('has fillable attributes', function (): void {
    $contentData = [
        'title' => 'Test Content',
        'slug' => 'test-content',
        'body' => 'Test content body',
        'excerpt' => 'Test excerpt',
        'status' => 'published',
        'entity_type' => 'article',
    ];

    $content = Content::create($contentData);

    expectModelAttributes($content, [
        'title' => 'Test Content',
        'slug' => 'test-content',
        'body' => 'Test content body',
        'excerpt' => 'Test excerpt',
        'status' => 'published',
        'entity_type' => 'article',
    ]);
});

it('belongs to many categories', function (): void {
    $category1 = Category::factory()->create(['name' => 'Technology']);
    $category2 = Category::factory()->create(['name' => 'Science']);
    
    $this->content->categories()->attach([$category1->id, $category2->id]);

    expect($this->content->categories)->toHaveCount(2);
    expect($this->content->categories->pluck('name')->toArray())->toContain('Technology', 'Science');
});

it('belongs to many authors', function (): void {
    $author1 = Author::factory()->create(['name' => 'John Doe']);
    $author2 = Author::factory()->create(['name' => 'Jane Smith']);
    
    $this->content->authors()->attach([$author1->id, $author2->id]);

    expect($this->content->authors)->toHaveCount(2);
    expect($this->content->authors->pluck('name')->toArray())->toContain('John Doe', 'Jane Smith');
});

it('belongs to many tags', function (): void {
    $tag1 = Tag::factory()->create(['name' => 'laravel']);
    $tag2 = Tag::factory()->create(['name' => 'php']);
    
    $this->content->tags()->attach([$tag1->id, $tag2->id]);

    expect($this->content->tags)->toHaveCount(2);
    expect($this->content->tags->pluck('name')->toArray())->toContain('laravel', 'php');
});

it('belongs to many locations', function (): void {
    $location1 = Location::factory()->create(['name' => 'Milan']);
    $location2 = Location::factory()->create(['name' => 'Rome']);
    
    $this->content->locations()->attach([$location1->id, $location2->id]);

    expect($this->content->locations)->toHaveCount(2);
    expect($this->content->locations->pluck('name')->toArray())->toContain('Milan', 'Rome');
});

it('has slug trait', function (): void {
    expect($this->content)->toHaveMethod('generateSlug');
    expect($this->content)->toHaveMethod('getSlug');
});

it('has tags trait', function (): void {
    expect($this->content)->toHaveMethod('attachTags');
    expect($this->content)->toHaveMethod('detachTags');
});

it('has multimedia trait', function (): void {
    expect($this->content)->toHaveMethod('addMedia');
    expect($this->content)->toHaveMethod('getMedia');
});

it('has dynamic contents trait', function (): void {
    expect($this->content)->toHaveMethod('getDynamicContents');
    expect($this->content)->toHaveMethod('setDynamicContents');
});

it('has path trait', function (): void {
    expect($this->content)->toHaveMethod('getPath');
    expect($this->content)->toHaveMethod('setPath');
});

it('has approvals trait', function (): void {
    expect($this->content)->toHaveMethod('approve');
    expect($this->content)->toHaveMethod('reject');
});

it('has children trait', function (): void {
    expect($this->content)->toHaveMethod('children');
    expect($this->content)->toHaveMethod('parent');
});

it('has validity trait', function (): void {
    expect($this->content)->toHaveMethod('isValid');
    expect($this->content)->toHaveMethod('isExpired');
});

it('has versions trait', function (): void {
    expect($this->content)->toHaveMethod('versions');
    expect($this->content)->toHaveMethod('createVersion');
});

it('has soft deletes trait', function (): void {
    $this->content->delete();
    
    expect($this->content->trashed())->toBeTrue();
    expect(Content::withTrashed()->find($this->content->id))->not->toBeNull();
});

it('has sortable trait', function (): void {
    expect($this->content)->toHaveMethod('moveOrder');
    expect($this->content)->toHaveMethod('getOrder');
});

it('has locks trait', function (): void {
    expect($this->content)->toHaveMethod('lock');
    expect($this->content)->toHaveMethod('unlock');
});

it('has validations trait', function (): void {
    expect($this->content)->toHaveMethod('getRules');
});

it('has searchable trait', function (): void {
    expect($this->content)->toHaveMethod('toSearchableArray');
    expect($this->content)->toHaveMethod('shouldBeSearchable');
});

it('has optimistic locking trait', function (): void {
    expect($this->content)->toHaveMethod('getLockVersion');
    expect($this->content)->toHaveMethod('incrementLockVersion');
});

it('can be created with specific attributes', function (): void {
    $contentData = [
        'title' => 'Custom Content',
        'slug' => 'custom-content',
        'body' => 'Custom content body',
        'excerpt' => 'Custom excerpt',
        'status' => 'draft',
        'entity_type' => 'page',
    ];

    $content = Content::create($contentData);

    expectModelAttributes($content, [
        'title' => 'Custom Content',
        'slug' => 'custom-content',
        'body' => 'Custom content body',
        'excerpt' => 'Custom excerpt',
        'status' => 'draft',
        'entity_type' => 'page',
    ]);
});

it('can be found by title', function (): void {
    $content = Content::factory()->create(['title' => 'Unique Content']);
    
    $foundContent = Content::where('title', 'Unique Content')->first();
    
    expect($foundContent->id)->toBe($content->id);
});

it('can be found by slug', function (): void {
    $content = Content::factory()->create(['slug' => 'unique-slug']);
    
    $foundContent = Content::where('slug', 'unique-slug')->first();
    
    expect($foundContent->id)->toBe($content->id);
});

it('can be found by status', function (): void {
    $publishedContent = Content::factory()->create(['status' => 'published']);
    $draftContent = Content::factory()->create(['status' => 'draft']);
    
    $publishedContents = Content::where('status', 'published')->get();
    $draftContents = Content::where('status', 'draft')->get();
    
    expect($publishedContents)->toHaveCount(1);
    expect($draftContents)->toHaveCount(1);
    expect($publishedContents->first()->id)->toBe($publishedContent->id);
    expect($draftContents->first()->id)->toBe($draftContent->id);
});

it('can be found by entity type', function (): void {
    $article = Content::factory()->create(['entity_type' => 'article']);
    $page = Content::factory()->create(['entity_type' => 'page']);
    
    $articles = Content::where('entity_type', 'article')->get();
    $pages = Content::where('entity_type', 'page')->get();
    
    expect($articles)->toHaveCount(1);
    expect($pages)->toHaveCount(1);
    expect($articles->first()->id)->toBe($article->id);
    expect($pages->first()->id)->toBe($page->id);
});

it('has proper timestamps', function (): void {
    $content = Content::factory()->create();
    
    expect($content->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($content->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('can be serialized to array', function (): void {
    $content = Content::factory()->create([
        'title' => 'Test Content',
        'slug' => 'test-content',
        'status' => 'published',
    ]);
    $contentArray = $content->toArray();
    
    expect($contentArray)->toHaveKey('id');
    expect($contentArray)->toHaveKey('title');
    expect($contentArray)->toHaveKey('slug');
    expect($contentArray)->toHaveKey('status');
    expect($contentArray)->toHaveKey('created_at');
    expect($contentArray)->toHaveKey('updated_at');
    expect($contentArray['title'])->toBe('Test Content');
    expect($contentArray['slug'])->toBe('test-content');
    expect($contentArray['status'])->toBe('published');
});

it('can be restored after soft delete', function (): void {
    $content = Content::factory()->create();
    $content->delete();
    
    expect($content->trashed())->toBeTrue();
    
    $content->restore();
    
    expect($content->trashed())->toBeFalse();
});

it('can be permanently deleted', function (): void {
    $content = Content::factory()->create();
    $contentId = $content->id;
    
    $content->forceDelete();
    
    expect(Content::withTrashed()->find($contentId))->toBeNull();
});
