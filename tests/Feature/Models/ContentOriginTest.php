<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\RecordOrigin;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! Schema::hasTable(CMSTables::Contents->value)) {
        $this->markTestSkipped('CMS contents table not migrated.');
    }

    setupCMSEntities();
});

it('records the provenance origin of a content', function (): void {
    $content = Content::factory()->create();

    $content->origin()->create([
        'source_key' => 'naxos_api',
        'source_label' => 'Repubblica',
        'external_id' => '1234',
        'url' => 'https://www.repubblica.it/example',
    ]);

    $content->refresh();

    expect($content->origin)->toBeInstanceOf(RecordOrigin::class)
        ->and($content->origin->source_key)->toBe('naxos_api')
        ->and($content->origin->source_label)->toBe('Repubblica')
        ->and($content->origin->external_id)->toBe('1234')
        ->and($content->origin->url)->toBe('https://www.repubblica.it/example');
});

it('allows a content without an origin', function (): void {
    $content = Content::factory()->create();

    expect($content->origin)->toBeNull();
});

it('resolves the referable back from the origin', function (): void {
    $content = Content::factory()->create([
        'valid_from' => now()->subDay(),
        'valid_to' => null,
    ]);

    $origin = $content->origin()->create([
        'source_key' => 'naxos_api',
        'external_id' => '99',
    ]);

    expect($origin->referable)->toBeInstanceOf(Content::class)
        ->and($origin->referable->getKey())->toBe($content->getKey());
});

/*
 * Provenance is integrity data and must remain resolvable regardless of the
 * content publication window. The Content validity global scope is a display
 * concern: it hides scheduled/expired records from the reverse traversal, so
 * administrative/provenance lookups must opt out of global scopes explicitly.
 */
it('does not resolve a scheduled content through the validity global scope but resolves it unscoped', function (): void {
    $content = Content::factory()->create([
        'valid_from' => now()->addWeek(),
        'valid_to' => null,
    ]);

    $origin = $content->origin()->create([
        'source_key' => 'naxos_api',
        'external_id' => '77',
    ]);

    expect($origin->referable)->toBeNull();

    $resolved = $origin->referable()->withoutGlobalScopes()->first();

    expect($resolved)->toBeInstanceOf(Content::class)
        ->and($resolved->getKey())->toBe($content->getKey());
});
