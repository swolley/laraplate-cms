<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Database\Factories\ContentFactory;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function pickRandomIds(mixed ...$args): array
{
    $method = new ReflectionMethod(ContentFactory::class, 'pickRandomIds');

    return $method->invoke(Content::factory(), ...$args);
}

it('picks the requested number of distinct ids from the pool', function (): void {
    $ids = pickRandomIds([10, 20, 30, 40, 50], 3);

    expect($ids)->toHaveCount(3)
        ->and(array_unique($ids))->toHaveCount(3)
        ->and(collect($ids)->every(fn (int $id): bool => in_array($id, [10, 20, 30, 40, 50], true)))->toBeTrue();
});

it('caps the pick at the pool size and returns an empty array for an empty pool', function (): void {
    expect(pickRandomIds([1, 2], 5))->toHaveCount(2)
        ->and(pickRandomIds([], 3))->toBe([]);
});

it('excludes the given id from the picked result', function (): void {
    $ids = pickRandomIds([1, 2, 3], 3, 2);

    expect($ids)->not->toContain(2)
        ->and($ids)->toHaveCount(2);
});

it('builds id pools that mirror each relation query', function (): void {
    setupCMSEntities([EntityType::Contents, EntityType::Contributors, EntityType::Categories]);
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();

    $pools = Content::factory()->buildRelationIdPools();

    // Categories/Tags are in scope right after creation, so they must appear.
    expect($pools['categories'])->toContain($category->id)
        ->and($pools['tags'])->toContain($tag->id);

    // Every pool mirrors its model's default-scoped ids (same set the previous
    // inRandomOrder() query drew from), for all four relations.
    expect($pools['contributors'])->toBe(Contributor::query()->pluck('id')->all())
        ->and($pools['categories'])->toBe(Category::query()->pluck('id')->all())
        ->and($pools['tags'])->toBe(Tag::query()->pluck('id')->all())
        ->and($pools['contents'])->toBe(Content::query()->pluck('id')->all());
});

it('attaches relations from the pools without any ORDER BY RAND query', function (): void {
    setupCMSEntities([EntityType::Contents, EntityType::Contributors, EntityType::Categories]);
    Category::factory()->count(2)->create();
    Tag::factory()->count(5)->create();
    $content = Content::factory()->create();

    $pools = Content::factory()->buildRelationIdPools();

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = mb_strtolower($query->sql);
    });

    Content::factory()->createRelations($content, null, $pools);

    // The whole point: no ORDER BY RAND() anywhere in the relation writes.
    expect(collect($queries)->contains(fn (string $sql): bool => str_contains($sql, 'rand(')))->toBeFalse();

    // Categories are in scope and ungated, so at least one must be attached,
    // proving the pool-based attach path actually writes pivots.
    $content->load('categories');

    expect($content->categories)->not->toBeEmpty()
        ->and($content->categories->pluck('id')->all())->each->toBeIn($pools['categories']);
});

it('is idempotent: a second createRelations run does not duplicate relations', function (): void {
    setupCMSEntities([EntityType::Contents, EntityType::Contributors, EntityType::Categories]);
    Category::factory()->count(2)->create();
    $content = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);

    $pools = Content::factory()->buildRelationIdPools();

    Content::factory()->createRelations($content, null, $pools);
    $content->load('categories');
    $first = $content->categories->count();

    expect($first)->toBeGreaterThan(0);

    Content::factory()->createRelations($content, null, $pools);
    $content->load('categories');

    expect($content->categories->count())->toBe($first);
});

it('checks existing relations once per chunk, not once per content', function (): void {
    setupCMSEntities([EntityType::Contents, EntityType::Contributors, EntityType::Categories]);
    Category::factory()->count(2)->create();
    $contents = Content::factory()->count(3)->create(['valid_from' => now()->subDay(), 'valid_to' => null]);

    $pools = Content::factory()->buildRelationIdPools();

    $exists_queries = 0;
    DB::listen(function ($query) use (&$exists_queries): void {
        if (str_contains(mb_strtolower($query->sql), 'exists')) {
            $exists_queries++;
        }
    });

    Content::factory()->createRelations($contents, null, $pools);

    // One existence check per relation (4), for the whole chunk — not 4 × 3 contents.
    expect($exists_queries)->toBe(4);
});
