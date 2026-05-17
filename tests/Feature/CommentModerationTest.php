<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

use Illuminate\Support\Facades\Event;
use Modules\Core\Events\ModificationApproved;
use Modules\Core\Events\ModificationRequiresModeration;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\ContentRating;
use Modules\Core\Models\Modification;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;

beforeEach(function (): void {
    Config::set('core.expose_crud_api', true);
    Config::set('app.locale', 'en');
    Modules\Core\Helpers\LocaleContext::set('en');
    $this->content = createMinimalTestContentForComments();
    $this->author = User::factory()->create();
    $this->moderator = User::factory()->create();
    $this->moderator->assignRole(Role::findOrCreate('superadmin', 'web'));
    $this->actingAs($this->author);
});

it('does not list pending comments until approved', function (): void {
    $comment = new Comment([
        'content_id' => $this->content->id,
        'user_id' => $this->author->id,
    ]);
    $comment->body = 'Awaiting approval';
    $comment->save();

    expect(Comment::query()->count())->toBe(0)
        ->and(Modification::query()->where('modifiable_type', Comment::class)->where('active', true)->exists())->toBeTrue();
});

it('dispatches ModificationRequiresModeration when modification is created', function (): void {
    Event::fake([ModificationRequiresModeration::class]);

    $comment = new Comment([
        'content_id' => $this->content->id,
        'user_id' => $this->author->id,
    ]);
    $comment->body = 'Trigger event';
    $comment->save();

    Event::assertDispatched(ModificationRequiresModeration::class);
});

it('publishes comment after human approval', function (): void {
    $comment = new Comment([
        'content_id' => $this->content->id,
        'user_id' => $this->author->id,
    ]);
    $comment->body = 'Human approved';
    $comment->save();

    $modification = Modification::query()
        ->where('modifiable_type', Comment::class)
        ->where('active', true)
        ->firstOrFail();

    $this->actingAs($this->moderator);
    $this->moderator->approve($modification, 'Looks good');

    expect(Comment::query()->count())->toBe(1)
        ->and(Comment::query()->first()?->body)->toBe('Human approved');

    expect($modification->fresh()?->active)->toBeFalsy();
});

it('never publishes comment after disapproval', function (): void {
    $comment = new Comment([
        'content_id' => $this->content->id,
        'user_id' => $this->author->id,
    ]);
    $comment->body = 'Rejected comment';
    $comment->save();

    $modification = Modification::query()
        ->where('modifiable_type', Comment::class)
        ->where('active', true)
        ->firstOrFail();

    $this->actingAs($this->moderator);
    $this->moderator->disapprove($modification);

    expect(Comment::query()->count())->toBe(0);
});

it('creates content rating when comment is approved with rating_score', function (): void {
    $comment = new Comment([
        'content_id' => $this->content->id,
        'user_id' => $this->author->id,
    ]);
    $comment->body = 'Great article';
    $comment->rating_score = 5;
    $comment->save();

    $modification = Modification::query()
        ->where('modifiable_type', Comment::class)
        ->where('active', true)
        ->firstOrFail();

    expect($modification->modifications)->toHaveKey('rating_score')
        ->and($modification->modifications['rating_score']['modified'])->toBe(5);

    $this->actingAs($this->moderator);
    $this->moderator->approve($modification);

    $rating = ContentRating::query()->first();

    expect($rating)->not->toBeNull()
        ->and($rating->content_id)->toBe($this->content->id)
        ->and($rating->user_id)->toBe($this->author->id)
        ->and($rating->score)->toBe(5)
        ->and($rating->comment_id)->toBe(Comment::query()->first()?->id);
});

it('updates existing content rating for same user and content on comment approval', function (): void {
    ContentRating::factory()->create([
        'content_id' => $this->content->id,
        'user_id' => $this->author->id,
        'score' => 2,
        'comment_id' => null,
    ]);

    $comment = new Comment([
        'content_id' => $this->content->id,
        'user_id' => $this->author->id,
    ]);
    $comment->body = 'Updated opinion';
    $comment->rating_score = 4;
    $comment->save();

    $modification = Modification::query()
        ->where('modifiable_type', Comment::class)
        ->where('active', true)
        ->firstOrFail();

    $this->actingAs($this->moderator);
    $this->moderator->approve($modification);

    expect(ContentRating::query()->count())->toBe(1)
        ->and(ContentRating::query()->first()?->score)->toBe(4)
        ->and(ContentRating::query()->first()?->comment_id)->not->toBeNull();
});

it('dispatches ModificationApproved after human approval', function (): void {
    Event::fake([ModificationApproved::class]);

    $comment = new Comment([
        'content_id' => $this->content->id,
        'user_id' => $this->author->id,
    ]);
    $comment->body = 'Approved body';
    $comment->save();

    $modification = Modification::query()
        ->where('modifiable_type', Comment::class)
        ->where('active', true)
        ->firstOrFail();

    $this->actingAs($this->moderator);
    $this->moderator->approve($modification);

    Event::assertDispatched(ModificationApproved::class, function (ModificationApproved $event): bool {
        return $event->modifiable instanceof Comment
            && $event->modifiable->body === 'Approved body';
    });
});
