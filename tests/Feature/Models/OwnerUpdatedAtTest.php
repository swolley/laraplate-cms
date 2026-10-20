<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Support\OwnerTimestamp;

uses(TestCase::class, RefreshDatabase::class);

/**
 * A content whose translations or relations change has its own row unchanged, yet the incremental search sync
 * and the freshness of the lists read its `updated_at`. So that column moves, with no event of the content and
 * no `lock_version` (D30, D37 of the revision-centric history review); `related()` stays out.
 */
beforeEach(function (): void {
    setupCMSEntities();
    CarbonImmutable::setTestNow('2026-10-19 09:00:00');
    $this->content = Content::factory()->create();
    $this->lock_version = content_row($this->content)->{Content::lockVersionColumn()};
    CarbonImmutable::setTestNow('2026-10-19 10:00:00');
    // RefreshDatabase wraps the test in one transaction, in which the creation above already touched the
    // content: a fresh helper stands for the separate request that changes it.
    app()->forgetInstance(OwnerTimestamp::class);
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function content_row(Content $content): object
{
    return $content->newQueryWithoutScopes()->whereKey($content->getKey())->toBase()->first();
}

function reference_of(string $relation): Model
{
    return match ($relation) {
        'tags' => Tag::factory()->create(),
        'categories' => Category::factory()->create(),
        'locations' => Location::factory()->create(),
        'contributors' => Contributor::factory()->create(),
    };
}

function expect_content_touched(Content $content, int|string|null $lock_version): void
{
    $row = content_row($content);

    expect((string) $row->updated_at)->toStartWith('2026-10-19 10:00:00')
        ->and($row->{Content::lockVersionColumn()})->toBe($lock_version);
}

it('moves the content updated_at when a relation is attached, without its events', function (string $relation): void {
    $reference = reference_of($relation);
    $fired = 0;
    Content::updated(function () use (&$fired): void {
        $fired++;
    });

    $this->content->{$relation}()->attach($reference->getKey());

    expect_content_touched($this->content, $this->lock_version);
    expect($fired)->toBe(0);
})->with(['tags', 'categories', 'locations', 'contributors']);

it('moves the content updated_at when a relation is detached', function (string $relation): void {
    $reference = reference_of($relation);
    CarbonImmutable::setTestNow('2026-10-19 09:30:00');
    $this->content->{$relation}()->attach($reference->getKey());
    CarbonImmutable::setTestNow('2026-10-19 10:00:00');
    app()->forgetInstance(OwnerTimestamp::class);

    $this->content->{$relation}()->detach($reference->getKey());

    expect_content_touched($this->content, $this->lock_version);
})->with(['tags', 'categories', 'locations', 'contributors']);

it('moves the content updated_at when the link is made from the other side', function (): void {
    $category = Category::factory()->create();

    $category->contents()->attach($this->content->getKey());

    expect_content_touched($this->content, $this->lock_version);
});

it('touches the content once for a sync of three tags in one transaction, and leaves the tags alone', function (): void {
    $tags = Tag::factory()->count(3)->create();
    $tag_updated_at = (string) $tags->first()->newQueryWithoutScopes()->whereKey($tags->first()->getKey())->toBase()->value('updated_at');
    $updates = 0;
    $this->content->getConnection()->listen(function ($query) use (&$updates): void {
        if (str_starts_with(mb_strtolower($query->sql), 'update') && str_contains($query->sql, $this->content->getTable())) {
            $updates++;
        }
    });

    $this->content->getConnection()->transaction(fn () => $this->content->tags()->sync($tags->modelKeys()));

    expect($updates)->toBe(1)
        ->and((string) $tags->first()->newQueryWithoutScopes()->whereKey($tags->first()->getKey())->toBase()->value('updated_at'))->toBe($tag_updated_at);
    expect_content_touched($this->content, $this->lock_version);
});

it('does not move the content updated_at for a related content', function (): void {
    $other = Content::factory()->create();
    $before = (string) content_row($this->content)->updated_at;

    $this->content->related()->attach($other->getKey());

    expect((string) content_row($this->content)->updated_at)->toBe($before);
});

it('moves the content updated_at when a translation is written or removed', function (string $change): void {
    if ($change === 'removed') {
        CarbonImmutable::setTestNow('2026-10-19 09:30:00');
        $this->content->setTranslation('de', ['title' => 'Titel', 'slug' => 'titel', 'components' => []]);
        CarbonImmutable::setTestNow('2026-10-19 10:00:00');
        app()->forgetInstance(OwnerTimestamp::class);
        $this->content->translations()->where('locale', 'de')->first()->delete();
    } else {
        $this->content->setTranslation('de', ['title' => 'Titel', 'slug' => 'titel', 'components' => []]);
    }

    expect_content_touched($this->content, $this->lock_version);
})->with(['written', 'removed']);
