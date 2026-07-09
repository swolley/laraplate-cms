<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\Translations\CommentTranslation;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Helpers\LocaleContext;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->content = createMinimalTestContentForComments();
    $this->user = Modules\Core\Models\User::factory()->create();
});

it('resolves content and user relations', function (): void {
    $comment = Comment::factory()->approved()->create([
        'content_id' => $this->content->id,
        'user_id' => $this->user->id,
    ]);

    expect($comment->content()->getParentKey())->toBe($this->content->id)
        ->and($comment->user()->getParentKey())->toBe($this->user->id)
        ->and($comment->rating()->getForeignKeyName())->toBe('comment_id');
});

it('resolves translations with and without fallback', function (): void {
    $comment = Comment::factory()->approved()->create([
        'content_id' => $this->content->id,
        'user_id' => $this->user->id,
    ]);

    CommentTranslation::query()->where('comment_id', $comment->id)->update(['locale' => 'en', 'body' => 'English body']);

    CommentTranslation::query()->create([
        'comment_id' => $comment->id,
        'locale' => 'fr',
        'body' => 'Corps français',
    ]);

    LocaleContext::set('it');
    expect($comment->getTranslation('fr', false)?->body)->toBe('Corps français');
    expect($comment->getTranslation('de'))->toBeInstanceOf(CommentTranslation::class);
    expect($comment->getOriginalTranslation()?->locale)->toBe('en');
});

it('handles rating score mutator and pending body detection', function (): void {
    $comment = Comment::factory()->approved()->create([
        'content_id' => $this->content->id,
        'user_id' => $this->user->id,
    ]);

    $comment->rating_score = '5';
    expect($comment->pending_rating_score)->toBe(5);

    $comment->rating_score = null;
    expect($comment->pending_rating_score)->toBeNull();

    $comment->inLocale(LocaleContext::get())->body = 'Pending text';
    expect($comment->hasPendingBodyForCurrentLocale())->toBeTrue();
});

it('delegates capture save to approval capture service', function (): void {
    $comment = Comment::factory()->make([
        'content_id' => $this->content->id,
        'user_id' => $this->user->id,
    ]);

    expect(Comment::captureSave($comment))->toBeBool();
});

it('uses approval array output when preview session is enabled', function (): void {
    $comment = Comment::factory()->approved()->create([
        'content_id' => $this->content->id,
        'user_id' => $this->user->id,
    ]);

    session(['preview' => true]);

    expect($comment->toArray())->toBeArray();
});
