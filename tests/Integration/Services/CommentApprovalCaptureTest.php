<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\ContentRating;
use Modules\CMS\Services\CommentApprovalCapture;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\User;

uses(TestCase::class, RefreshDatabase::class);

it('enriches diffs with parent id changes', function (): void {
    $comment = new Comment();
    $comment->parent_id = 123;

    $diff = CommentApprovalCapture::enrichDiff($comment, []);

    expect($diff['parent_id']['original'])->toBeNull()
        ->and($diff['parent_id']['modified'])->toBe(123);
});

it('allows capture when there are no modifications', function (): void {
    expect(CommentApprovalCapture::capture(new Comment()))->toBeTrue();
});

it('enriches diffs with pending rating score changes', function (): void {
    setupCMSEntities();

    $content = createMinimalTestContentForComments();
    $user = User::factory()->create();

    ContentRating::factory()->create([
        'content_id' => $content->id,
        'user_id' => $user->id,
        'score' => 4,
    ]);

    $comment = new Comment([
        'content_id' => $content->id,
        'user_id' => $user->id,
    ]);
    $comment->rating_score = 2;

    $diff = CommentApprovalCapture::enrichDiff($comment, []);

    expect($diff['rating_score']['original'])->toBe(4)
        ->and($diff['rating_score']['modified'])->toBe(2);
});

it('falls back to an empty encoded diff when json encoding fails', function (): void {
    $stream = fopen('php://memory', 'rb');

    try {
        $method = new ReflectionMethod(CommentApprovalCapture::class, 'encodeDiff');
        $method->setAccessible(true);

        expect($method->invoke(null, [
            'body' => [
                'original' => null,
                'modified' => $stream,
            ],
        ]))->toBe('');
    } finally {
        if (is_resource($stream)) {
            fclose($stream);
        }
    }
});
