<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Models\Preset;
use Modules\Cms\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! Schema::hasTable('presets')) {
        $this->markTestSkipped('Preset scopes require CMS schema.');
    }

    setupCmsEntities();
});

it('scopes to active presets whose entity is active and matches the table type', function (): void {
    expect(Preset::query()->forActiveEntityOfType(EntityType::CONTENTS)->exists())->toBeTrue();
});

it('supports filter option labels when composed with entity join and ordering', function (): void {
    $options = Preset::query()
        ->forActiveEntityOfType(EntityType::CONTENTS)
        ->join('entities', 'presets.entity_id', '=', 'entities.id')
        ->orderBy('entities.name')
        ->orderBy('presets.name')
        ->get(['presets.id', 'presets.name', 'presets.entity_id', 'entities.name'])
        ->mapWithKeys(static fn (Preset $preset): array => [$preset->id => $preset->entity->name . ' - ' . $preset->name])
        ->all();

    expect($options)->toBeArray()
        ->and($options)->not->toBeEmpty();

    foreach (array_keys($options) as $key) {
        expect($key)->toBeInt();
    }

    foreach ($options as $label) {
        expect($label)->toBeString()->not->toBeEmpty();
    }
});
