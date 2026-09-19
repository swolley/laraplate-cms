<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Marks an existing content as extended by writing the guarded column straight to the row,
 * standing in for the extender create-path (Task 3) so the scope can be tested in isolation.
 */
function markContentExtended(Content $content, string $alias = 'cms.stub_extended'): void
{
    DB::table(CMSTables::Contents->value)
        ->where('id', $content->getKey())
        ->update(['extended_type' => $alias]);
}

beforeEach(function (): void {
    setupCMSEntities([EntityType::Contents]);
});

it('hides an extended content from the default query', function (): void {
    $content = Content::factory()->create();
    markContentExtended($content);

    expect(Content::query()->find($content->getKey()))->toBeNull()
        ->and(Content::query()->whereKey($content->getKey())->count())->toBe(0);
});

it('returns extended contents under withExtended()', function (): void {
    $content = Content::factory()->create();
    markContentExtended($content);

    $found = Content::withExtended()->find($content->getKey());

    expect($found)->not->toBeNull()
        ->and($found->getKey())->toBe($content->getKey())
        ->and($found->extended_type)->toBe('cms.stub_extended');
});

it('keeps a normal content visible by default', function (): void {
    $content = Content::factory()->create();

    expect(Content::query()->find($content->getKey()))->not->toBeNull();
});

it('leaves the soft-delete scope applied under withExtended()', function (): void {
    $content = Content::factory()->create();
    markContentExtended($content);
    $content->delete();

    expect(Content::withExtended()->find($content->getKey()))->toBeNull()
        ->and(Content::withExtended()->withTrashed()->find($content->getKey()))->not->toBeNull();
});
