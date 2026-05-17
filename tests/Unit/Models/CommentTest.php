<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

use Modules\CMS\Models\Comment;
use Modules\CMS\Models\Content;
use Modules\Core\Helpers\LocaleContext;
use Modules\Core\Models\Modification;
use Modules\Core\Models\User;

beforeEach(function (): void {
    setupCMSEntities();
    $this->content = createMinimalTestContentForComments();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    LocaleContext::set('en');
});

it('requires approval when body is pending for current locale', function (): void {
    $comment = new Comment([
        'content_id' => $this->content->id,
        'user_id' => $this->user->id,
    ]);
    $comment->body = 'Pending comment';

    $method = new ReflectionMethod(Comment::class, 'requiresApprovalWhen');
    $method->setAccessible(true);

    expect($method->invoke($comment, []))->toBeTrue();
});

it('creates modification with body locale and content_id on save', function (): void {
    $comment = new Comment([
        'content_id' => $this->content->id,
        'user_id' => $this->user->id,
    ]);
    $comment->body = 'Needs moderation';
    $comment->save();

    $modification = Modification::query()
        ->where('modifiable_type', Comment::class)
        ->where('active', true)
        ->first();

    expect($modification)->not->toBeNull()
        ->and($modification->modifications['body']['modified'])->toBe('Needs moderation')
        ->and($modification->modifications['locale']['modified'])->toBe('en')
        ->and($modification->modifications['content_id']['modified'])->toBe($this->content->id);

    expect(Comment::query()->count())->toBe(0);
});

it('belongs to content', function (): void {
    $comment = Comment::factory()->approved()->create([
        'content_id' => $this->content->id,
        'user_id' => $this->user->id,
    ]);

    expect($comment->content->is($this->content))->toBeTrue();
    expect($this->content->comments()->count())->toBe(1);
});

it('uses cms comments table name from enum', function (): void {
    expect((new Comment())->getTable())->toBe(CMSTables::Comments->value);
});
