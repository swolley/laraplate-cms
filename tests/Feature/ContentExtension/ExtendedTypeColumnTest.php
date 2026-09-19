<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('adds a nullable extended_type column to the contents table', function (): void {
    expect(Schema::hasColumn(CMSTables::Contents->value, 'extended_type'))->toBeTrue();
});

it('defaults extended_type to null for a normal content', function (): void {
    setupCMSEntities([EntityType::Contents]);

    $content = Content::factory()->create();

    $stored = DB::table(CMSTables::Contents->value)
        ->where('id', $content->getKey())
        ->value('extended_type');

    expect($stored)->toBeNull();
});
