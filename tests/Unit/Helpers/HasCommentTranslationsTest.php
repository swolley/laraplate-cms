<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\Translations\CommentTranslation;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Helpers\LocaleContext;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('app.locale', 'en');
    LocaleContext::set('en');
});

it('returns current locale body when translation exists', function (): void {
    $comment = Comment::query()->create([
        'content_id' => createMinimalTestContentForComments()->id,
        'user_id' => \Modules\Core\Models\User::factory()->create()->id,
    ]);

    CommentTranslation::query()->create([
        'comment_id' => $comment->id,
        'locale' => 'en',
        'body' => 'English body',
    ]);

    LocaleContext::set('en');

    expect($comment->fresh()->body)->toBe('English body');
});

it('falls back to oldest created translation when current locale missing', function (): void {
    $comment = Comment::query()->create([
        'content_id' => createMinimalTestContentForComments()->id,
        'user_id' => \Modules\Core\Models\User::factory()->create()->id,
    ]);

    $italian = new CommentTranslation([
        'comment_id' => $comment->id,
        'locale' => 'it',
        'body' => 'Corpo italiano',
    ]);
    $italian->created_at = now()->subDay();
    $italian->updated_at = now()->subDay();
    $italian->save();

    LocaleContext::set('en');

    expect($comment->fresh()->body)->toBe('Corpo italiano');
});

it('does not fall back to config app locale when older original is another locale', function (): void {
    Config::set('app.locale', 'en');

    $comment = Comment::query()->create([
        'content_id' => createMinimalTestContentForComments()->id,
        'user_id' => \Modules\Core\Models\User::factory()->create()->id,
    ]);

    $french = new CommentTranslation([
        'comment_id' => $comment->id,
        'locale' => 'fr',
        'body' => 'Texte français',
    ]);
    $french->created_at = now()->subDays(2);
    $french->updated_at = now()->subDays(2);
    $french->save();

    $english = new CommentTranslation([
        'comment_id' => $comment->id,
        'locale' => 'en',
        'body' => 'Later English',
    ]);
    $english->created_at = now()->subDay();
    $english->updated_at = now()->subDay();
    $english->save();

    LocaleContext::set('de');

    expect($comment->fresh()->body)->toBe('Texte français');
});
