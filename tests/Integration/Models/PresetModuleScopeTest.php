<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Preset;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Enums\CoreTables;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Insert an entity of the given type and one preset under it, bypassing model events.
 */
function cmsScopePresetOfType(string $type): int
{
    $connection = (new Preset)->getConnection();
    $suffix = uniqid();

    $entity_id = $connection->table(CoreTables::Entities->value)->insertGetId([
        'name' => "{$type}_{$suffix}",
        'slug' => "{$type}-{$suffix}",
        'type' => $type,
    ]);

    return $connection->table(CoreTables::Presets->value)->insertGetId([
        'entity_id' => $entity_id,
        'name' => "preset_{$suffix}",
    ]);
}

it('reads only presets whose own entity is a CMS type', function (): void {
    $cms_preset = cmsScopePresetOfType(EntityType::Contents->value);
    $foreign_preset = cmsScopePresetOfType('not_a_cms_type');

    $ids = Preset::query()->withoutGlobalScopes()->pluck('id')->all();

    expect($ids)->toContain($cms_preset)
        ->not->toContain($foreign_preset);
});
