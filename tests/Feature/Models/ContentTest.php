<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Models\Author;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Location;
use Modules\Cms\Models\Tag;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->content = Content::factory()->create();
});

it('can be created with factory', function (): void {
    expect($this->content)->toBeInstanceOf(Content::class);
    expect($this->content->id)->not->toBeNull();
});

it('has translatable attributes', function (): void {
    $content = Content::factory()->create();

    // Set translation for default locale
    $default_locale = config('app.locale');
    $content->setTranslation($default_locale, [
        'title' => 'Test Content',
        'slug' => 'test-content',
        'components' => [
            'body' => 'Test content body',
            'excerpt' => 'Test excerpt',
        ],
    ]);
    $content->save();

    expect($content->title)->toBe('Test Content');
    expect($content->slug)->toBe('test-content');
    expect($content->body)->toBe('Test content body');
    expect($content->excerpt)->toBe('Test excerpt');
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
    expect(method_exists($this->content, 'generateSlug'))->toBeTrue();
    expect(method_exists($this->content, 'getSlug'))->toBeTrue();
});

it('has tags trait', function (): void {
    expect(method_exists($this->content, 'attachTags'))->toBeTrue();
    expect(method_exists($this->content, 'detachTags'))->toBeTrue();
});

it('has multimedia trait', function (): void {
    expect(method_exists($this->content, 'addMedia'))->toBeTrue();
    expect(method_exists($this->content, 'getMedia'))->toBeTrue();
});

it('has dynamic contents trait', function (): void {
    expect(method_exists($this->content, 'getDynamicContents'))->toBeTrue();
    expect(method_exists($this->content, 'setDynamicContents'))->toBeTrue();
});

it('has path trait', function (): void {
    expect(method_exists($this->content, 'getPath'))->toBeTrue();
    expect(method_exists($this->content, 'setPath'))->toBeTrue();
});

it('has approvals trait', function (): void {
    expect(method_exists($this->content, 'approve'))->toBeTrue();
    expect(method_exists($this->content, 'reject'))->toBeTrue();
});

it('has children trait', function (): void {
    expect(method_exists($this->content, 'children'))->toBeTrue();
    expect(method_exists($this->content, 'parent'))->toBeTrue();
});

it('has validity trait', function (): void {
    expect(method_exists($this->content, 'isValid'))->toBeTrue();
    expect(method_exists($this->content, 'isExpired'))->toBeTrue();
});

it('has versions trait', function (): void {
    expect(method_exists($this->content, 'versions'))->toBeTrue();
    expect(method_exists($this->content, 'createVersion'))->toBeTrue();
});

it('has soft deletes trait', function (): void {
    $this->content->delete();

    expect($this->content->trashed())->toBeTrue();
    expect(Content::withTrashed()->find($this->content->id))->not->toBeNull();
});

it('has sortable trait', function (): void {
    expect(method_exists($this->content, 'moveOrder'))->toBeTrue();
    expect(method_exists($this->content, 'getOrder'))->toBeTrue();
});

it('has locks trait', function (): void {
    expect(method_exists($this->content, 'lock'))->toBeTrue();
    expect(method_exists($this->content, 'unlock'))->toBeTrue();
});

it('has validations trait', function (): void {
    expect(method_exists($this->content, 'getRules'))->toBeTrue();
});

it('has searchable trait', function (): void {
    expect(method_exists($this->content, 'toSearchableArray'))->toBeTrue();
    expect(method_exists($this->content, 'shouldBeSearchable'))->toBeTrue();
});

it('has optimistic locking trait', function (): void {
    expect(method_exists($this->content, 'getLockVersion'))->toBeTrue();
    expect(method_exists($this->content, 'incrementLockVersion'))->toBeTrue();
});

it('can be created with specific translation attributes', function (): void {
    $content = Content::factory()->create();
    $default_locale = config('app.locale');
    $content->setTranslation($default_locale, [
        'title' => 'Custom Content',
        'slug' => 'custom-content',
        'components' => [
            'body' => 'Custom content body',
            'excerpt' => 'Custom excerpt',
        ],
    ]);
    $content->save();

    expect($content->title)->toBe('Custom Content');
    expect($content->slug)->toBe('custom-content');
    expect($content->body)->toBe('Custom content body');
    expect($content->excerpt)->toBe('Custom excerpt');
});

it('can be found by title through translation', function (): void {
    $content = Content::factory()->create();
    $default_locale = config('app.locale');
    $content->setTranslation($default_locale, [
        'title' => 'Unique Content',
        'slug' => 'unique-content',
    ]);
    $content->save();

    // Title is now in translations, so we need to search through the relation
    $foundContent = Content::whereHas('translations', function ($q): void {
        $q->where('title', 'Unique Content');
    })->first();

    expect($foundContent->id)->toBe($content->id);
    expect($foundContent->title)->toBe('Unique Content');
});

it('can be found by slug through translation', function (): void {
    $content = Content::factory()->create();
    $default_locale = config('app.locale');
    $content->setTranslation($default_locale, [
        'title' => 'Test Content',
        'slug' => 'unique-slug',
    ]);
    $content->save();

    // Slug is now in translations, so we need to search through the relation
    $foundContent = Content::whereHas('translations', function ($q): void {
        $q->where('slug', 'unique-slug');
    })->first();

    expect($foundContent->id)->toBe($content->id);
    expect($foundContent->slug)->toBe('unique-slug');
});

it('has translations trait', function (): void {
    expect(method_exists($this->content, 'translations'))->toBeTrue();
    expect(method_exists($this->content, 'translation'))->toBeTrue();
    expect(method_exists($this->content, 'getTranslation'))->toBeTrue();
    expect(method_exists($this->content, 'setTranslation'))->toBeTrue();
    expect(method_exists($this->content, 'hasTranslation'))->toBeTrue();
    expect(method_exists($this->content, 'getTranslatableFields'))->toBeTrue();
});

it('has proper timestamps', function (): void {
    $content = Content::factory()->create();

    expect($content->created_at)->toBeInstanceOf(Carbon\Carbon::class);
    expect($content->updated_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('can be serialized to array with translations', function (): void {
    $content = Content::factory()->create();
    $default_locale = config('app.locale');
    $content->setTranslation($default_locale, [
        'title' => 'Test Content',
        'slug' => 'test-content',
    ]);
    $content->save();

    $contentArray = $content->toArray();

    expect($contentArray)->toHaveKey('id');
    expect($contentArray)->toHaveKey('title');
    expect($contentArray)->toHaveKey('slug');
    expect($contentArray)->toHaveKey('created_at');
    expect($contentArray)->toHaveKey('updated_at');
    expect($contentArray['title'])->toBe('Test Content');
    expect($contentArray['slug'])->toBe('test-content');
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
