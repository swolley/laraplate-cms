<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Translations\CommentTranslation;
use Modules\CMS\Models\Translations\ContentTranslation;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->content = createMinimalTestContentForComments();
    $this->user = Modules\Core\Models\User::factory()->create();
});

it('resolves the parent comment from a comment translation', function (): void {
    $comment = Comment::factory()->approved()->create([
        'content_id' => $this->content->id,
        'user_id' => $this->user->id,
    ]);

    $translation = $comment->getTranslation('en');
    expect($translation)->toBeInstanceOf(CommentTranslation::class);
    expect($translation->comment())->toBeInstanceOf(BelongsTo::class);
    expect($translation->comment()->getParentKey())->toBe($comment->id);
});

it('resolves the parent content from a content translation', function (): void {
    $translation = ContentTranslation::query()
        ->where('content_id', $this->content->id)
        ->firstOrFail();

    expect($translation->content())->toBeInstanceOf(BelongsTo::class);
    expect($translation->content()->getRelated())->toBeInstanceOf(Content::class);
    expect($translation->content()->getForeignKeyName())->toBe('content_id');
});
