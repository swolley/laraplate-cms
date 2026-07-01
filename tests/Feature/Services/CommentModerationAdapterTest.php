<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Comment;
use Modules\CMS\Services\CommentModerationAdapter;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Modification;
use Modules\Core\Models\User;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->content = createMinimalTestContentForComments();
    $this->user = User::factory()->create();
});

it('builds moderation request from modification payload', function (): void {
    $modification = Modification::query()->create([
        'modifiable_type' => Comment::class,
        'modifiable_id' => null,
        'modifier_id' => $this->user->id,
        'modifier_type' => User::class,
        'active' => true,
        'is_update' => false,
        'approvers_required' => 1,
        'disapprovers_required' => 1,
        'md5' => md5('comment-mod'),
        'modifications' => [
            'content_id' => ['original' => null, 'modified' => (int) $this->content->id],
            'body' => ['original' => null, 'modified' => 'Great article!'],
            'locale' => ['original' => null, 'modified' => 'en'],
        ],
    ]);

    $request = app(CommentModerationAdapter::class)->build($modification);

    expect($request->input->subjectText)->toBe('Great article!')
        ->and($request->input->locale)->toBe('en')
        ->and($request->input->profile)->toBe(CommentModerationAdapter::PROFILE)
        ->and($request->input->contextSections['Article title'])->toBe('Comment test content')
        ->and($request->input->contextSections['Article excerpt'])->toBeString()
        ->and($request->input->contextSections)->not->toHaveKey('Parent comment being replied to')
        ->and($request->systemPrompt)->toContain('content moderation classifier')
        ->and($request->userPrompt)->toContain('Great article!');
});

it('includes parent comment context when parent_id is in the modification', function (): void {
    $parent = Comment::factory()
        ->for($this->content, 'content')
        ->for($this->user, 'user')
        ->withBody('Original thread message.', 'en')
        ->create();

    $modification = Modification::query()->create([
        'modifiable_type' => Comment::class,
        'modifiable_id' => null,
        'modifier_id' => $this->user->id,
        'modifier_type' => User::class,
        'active' => true,
        'is_update' => false,
        'approvers_required' => 1,
        'disapprovers_required' => 1,
        'md5' => md5('reply-mod'),
        'modifications' => [
            'content_id' => ['original' => null, 'modified' => (int) $this->content->id],
            'parent_id' => ['original' => null, 'modified' => $parent->id],
            'body' => ['original' => null, 'modified' => 'Thanks for explaining that!'],
            'locale' => ['original' => null, 'modified' => 'en'],
        ],
    ]);

    $request = app(CommentModerationAdapter::class)->build($modification);

    expect($request->input->contextSections['Parent comment being replied to'])
        ->toBe('Original thread message.')
        ->and($request->userPrompt)->toContain('Original thread message.')
        ->and($request->userPrompt)->toContain('Thanks for explaining that!');
});

it('falls back to available title and skips missing parent context', function (): void {
    $modification = Modification::query()->create([
        'modifiable_type' => Comment::class,
        'modifiable_id' => null,
        'modifier_id' => $this->user->id,
        'modifier_type' => User::class,
        'active' => true,
        'is_update' => false,
        'approvers_required' => 1,
        'disapprovers_required' => 1,
        'md5' => md5('missing-parent-mod'),
        'modifications' => [
            'content_id' => ['original' => null, 'modified' => (int) $this->content->id],
            'parent_id' => ['original' => null, 'modified' => 999_999],
            'body' => ['original' => null, 'modified' => 'Is this still relevant?'],
            'locale' => ['original' => null, 'modified' => 'fr'],
        ],
    ]);

    $request = app(CommentModerationAdapter::class)->build($modification);

    expect($request->input->contextSections['Article title'])->toBe('Comment test content')
        ->and($request->input->contextSections)->not->toHaveKey('Parent comment being replied to');
});

it('normalizes and truncates plain text excerpts', function (): void {
    $content = $this->content->fresh();
    $content->setRawAttributes(array_merge($content->getAttributes(), [
        'short_content' => '<p>' . str_repeat('Long text ', 220) . '</p>',
    ]), sync: true);

    $adapter = app(CommentModerationAdapter::class);
    $method = new ReflectionMethod(CommentModerationAdapter::class, 'plainTextExcerpt');
    $method->setAccessible(true);

    expect($method->invoke($adapter, $content, 'en', 20))->toBe('Long text Long text …');
});

it('supports comment modifications only', function (): void {
    $adapter = app(CommentModerationAdapter::class);

    $comment_modification = Modification::query()->create([
        'modifiable_type' => Comment::class,
        'modifiable_id' => 1,
        'modifier_id' => $this->user->id,
        'modifier_type' => User::class,
        'active' => true,
        'is_update' => false,
        'md5' => md5('x'),
        'modifications' => [],
    ]);

    expect($adapter->supports($comment_modification))->toBeTrue()
        ->and($adapter->supports(new Modification([
            'modifiable_type' => User::class,
            'modifiable_id' => 1,
            'active' => true,
            'md5' => md5('y'),
            'modifications' => [],
        ])))->toBeFalse();
});
