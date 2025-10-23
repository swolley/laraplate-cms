<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Models\Author;
use Modules\Cms\Models\Content;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->author = Author::factory()->create();
});

it('can be created with factory', function (): void {
    expect($this->author)->toBeInstanceOf(Author::class);
    expect($this->author->id)->not->toBeNull();
});

it('has fillable attributes', function (): void {
    $authorData = [
        'name' => 'John Doe',
    ];

    $author = Author::create($authorData);

    expectModelAttributes($author, [
        'name' => 'John Doe',
    ]);
});

it('has hidden attributes', function (): void {
    $author = Author::factory()->create();
    $authorArray = $author->toArray();

    expect($authorArray)->not->toHaveKey('user_id');
    expect($authorArray)->not->toHaveKey('user');
    expect($authorArray)->not->toHaveKey('created_at');
    expect($authorArray)->not->toHaveKey('updated_at');
});

it('belongs to many contents', function (): void {
    $content1 = Content::factory()->create(['title' => 'Article 1']);
    $content2 = Content::factory()->create(['title' => 'Article 2']);

    $this->author->contents()->attach([$content1->id, $content2->id]);

    expect($this->author->contents)->toHaveCount(2);
    expect($this->author->contents->pluck('title')->toArray())->toContain('Article 1', 'Article 2');
});

it('has dynamic contents trait', function (): void {
    expect($this->author)->toHaveMethod('getDynamicContents');
    expect($this->author)->toHaveMethod('setDynamicContents');
});

it('has multimedia trait', function (): void {
    expect($this->author)->toHaveMethod('addMedia');
    expect($this->author)->toHaveMethod('getMedia');
});

it('has tags trait', function (): void {
    expect($this->author)->toHaveMethod('attachTags');
    expect($this->author)->toHaveMethod('detachTags');
});

it('has versions trait', function (): void {
    expect($this->author)->toHaveMethod('versions');
    expect($this->author)->toHaveMethod('createVersion');
});

it('has soft deletes trait', function (): void {
    $this->author->delete();

    expect($this->author->trashed())->toBeTrue();
    expect(Author::withTrashed()->find($this->author->id))->not->toBeNull();
});

it('has validations trait', function (): void {
    expect($this->author)->toHaveMethod('getRules');
});

it('can be created with specific attributes', function (): void {
    $authorData = [
        'name' => 'Jane Smith',
    ];

    $author = Author::create($authorData);

    expectModelAttributes($author, [
        'name' => 'Jane Smith',
    ]);
});

it('can be found by name', function (): void {
    $author = Author::factory()->create(['name' => 'Unique Author']);

    $foundAuthor = Author::where('name', 'Unique Author')->first();

    expect($foundAuthor->id)->toBe($author->id);
});

it('has proper timestamps', function (): void {
    $author = Author::factory()->create();

    expect($author->created_at)->toBeInstanceOf(Carbon\Carbon::class);
    expect($author->updated_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('can be serialized to array', function (): void {
    $author = Author::factory()->create([
        'name' => 'Test Author',
    ]);
    $authorArray = $author->toArray();

    expect($authorArray)->toHaveKey('id');
    expect($authorArray)->toHaveKey('name');
    expect($authorArray['name'])->toBe('Test Author');
});

it('can be restored after soft delete', function (): void {
    $author = Author::factory()->create();
    $author->delete();

    expect($author->trashed())->toBeTrue();

    $author->restore();

    expect($author->trashed())->toBeFalse();
});

it('can be permanently deleted', function (): void {
    $author = Author::factory()->create();
    $authorId = $author->id;

    $author->forceDelete();

    expect(Author::withTrashed()->find($authorId))->toBeNull();
});
