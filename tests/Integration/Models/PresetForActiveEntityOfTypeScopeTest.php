<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Casts\EntityType;
use Modules\Core\Enums\CoreTables;
use Modules\CMS\Models\Preset;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! Schema::hasTable(CoreTables::Presets->value)) {
        $this->markTestSkipped('Preset scopes require CMS schema.');
    }

    setupCMSEntities();
});

it('scopes to active presets whose entity is active and matches the table type', function (): void {
    expect(Preset::query()->forActiveEntityOfType(EntityType::Contents)->exists())->toBeTrue();
});

it('supports filter option labels when composed with entity join and ordering', function (): void {
    $options = Preset::query()
        ->forActiveEntityOfType(EntityType::Contents)
        ->join(CoreTables::Entities->value, CoreTables::Presets->value.'.entity_id', '=', CoreTables::Entities->value.'.id')
        ->orderBy(CoreTables::Entities->value.'.name')
        ->orderBy(CoreTables::Presets->value.'.name')
        ->get([
            CoreTables::Presets->value.'.id',
            CoreTables::Presets->value.'.name',
            CoreTables::Presets->value.'.entity_id',
            CoreTables::Entities->value.'.name',
        ])
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
