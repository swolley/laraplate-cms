<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\CMS\Import\Dto\ImportCategoryDto;
use Modules\CMS\Import\Dto\ImportTagDto;
use Modules\CMS\Import\Upserters\CategoryUpserter;
use Modules\CMS\Import\Upserters\TagUpserter;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Services\DynamicContentsService;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config(['scout.driver' => 'null']);
    Queue::fake();
    setupCMSEntities();
    DynamicContentsService::reset();
});

/**
 * @param  'tag'|'category'  $kind
 * @return class-string<Tag|Category>
 */
function taxonomy_model(string $kind): string
{
    return $kind === 'tag' ? Tag::class : Category::class;
}

/**
 * @param  'tag'|'category'  $kind
 */
function upsert_taxonomy_from_source(string $kind, int $externalId, ?int $position): int
{
    if ($kind === 'tag') {
        return resolve(TagUpserter::class)->upsert(new ImportTagDto(
            externalId: $externalId,
            name: "Tag {$externalId}",
            slug: "tag-{$externalId}",
            type: null,
            orderColumn: $position,
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
            sourceType: 'fixture@tenant',
        ));
    }

    return resolve(CategoryUpserter::class)->upsert(new ImportCategoryDto(
        externalId: $externalId,
        name: "Category {$externalId}",
        slug: "category-{$externalId}",
        parentExternalId: null,
        components: [],
        sharedComponents: [],
        isActive: true,
        orderColumn: $position,
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        sourceType: 'fixture@tenant',
    ));
}

function taxonomy_position(string $kind, int $id): int
{
    return taxonomy_model($kind)::query()->withoutGlobalScopes()->whereKey($id)->firstOrFail()->order_column;
}

it('gives a new record the position the source gives it', function (string $kind): void {
    taxonomy_model($kind)::factory()->count(3)->create();

    $id = upsert_taxonomy_from_source($kind, 5001, 40);

    expect(taxonomy_position($kind, $id))->toBe(40);
})->with(['tag', 'category']);

it('puts a new record last when the source has no order of its own', function (string $kind): void {
    $last = taxonomy_model($kind)::factory()->count(3)->create()->last();

    $id = upsert_taxonomy_from_source($kind, 5001, null);

    expect(taxonomy_position($kind, $id))->toBe(taxonomy_position($kind, $last->getKey()) + 1);
})->with(['tag', 'category']);

it('keeps the position of an existing record when the source has no order of its own', function (string $kind): void {
    $id = upsert_taxonomy_from_source($kind, 5001, null);
    taxonomy_model($kind)::query()->withoutGlobalScopes()->whereKey($id)->update(['order_column' => 9]);

    upsert_taxonomy_from_source($kind, 5001, null);

    expect(taxonomy_position($kind, $id))->toBe(9);
})->with(['tag', 'category']);

it('moves an existing record to the position the source gives it', function (string $kind): void {
    $id = upsert_taxonomy_from_source($kind, 5001, null);

    upsert_taxonomy_from_source($kind, 5001, 7);

    expect(taxonomy_position($kind, $id))->toBe(7);
})->with(['tag', 'category']);
