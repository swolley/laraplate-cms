<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! Schema::hasTable(CMSTables::Contents->value)) {
        $this->markTestSkipped('CMS contents table not migrated.');
    }

    setupCMSEntities();
});

it('persists origin label and url on content', function (): void {
    $content = Content::factory()->create([
        'origin_label' => 'Repubblica',
        'origin_url' => 'https://www.repubblica.it/example',
    ]);

    $content->refresh();

    expect($content->origin_label)->toBe('Repubblica')
        ->and($content->origin_url)->toBe('https://www.repubblica.it/example');
});

it('allows nullable origin fields', function (): void {
    $content = Content::factory()->create([
        'origin_label' => null,
        'origin_url' => null,
    ]);

    expect($content->origin_label)->toBeNull()
        ->and($content->origin_url)->toBeNull();
});

it('validates origin url format on create', function (): void {
    $content = Content::factory()->make([
        'origin_url' => 'not-a-url',
    ]);

    expect(fn () => $content->validateWithRules('create'))->toThrow(Illuminate\Validation\ValidationException::class);
});
