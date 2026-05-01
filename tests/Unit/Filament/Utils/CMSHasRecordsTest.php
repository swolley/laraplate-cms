<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Entity;
use Modules\CMS\Models\Pivot\Presettable;
use Modules\CMS\Models\Preset;
use Modules\CMS\Tests\TestCase;
use Modules\CMS\Tests\Unit\Filament\Utils\CMSHasRecordsEntityHarness;
use Modules\CMS\Tests\Unit\Filament\Utils\CMSHasRecordsTraitHarness;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (class_exists(Modules\Core\Services\DynamicContentsService::class)) {
        Modules\Core\Services\DynamicContentsService::reset();
    }

    Cache::flush();
});

function createSecondContentsEntityWithPreset(): Entity
{
    $secondary_name = 'contents_secondary_' . uniqid();
    $secondary = Entity::query()->create([
        'name' => $secondary_name,
        'type' => EntityType::CONTENTS,
        'slug' => Str::slug($secondary_name),
    ]);

    $secondaryPreset = Preset::query()->firstOrCreate(
        ['entity_id' => $secondary->id, 'name' => 'default'],
        ['entity_id' => $secondary->id, 'name' => 'default'],
    );

    Presettable::query()->firstOrCreate(
        ['entity_id' => $secondary->id, 'preset_id' => $secondaryPreset->id],
        ['entity_id' => $secondary->id, 'preset_id' => $secondaryPreset->id],
    );

    return $secondary;
}

it('returns filament tabs with badges when multiple content entities exist and counts are present', function (): void {
    setupCMSEntities([EntityType::CONTENTS]);

    $secondaryEntity = createSecondContentsEntityWithPreset();

    if (class_exists(Modules\Core\Services\DynamicContentsService::class)) {
        Modules\Core\Services\DynamicContentsService::reset();
    }

    $primaryPresettable = Presettable::query()
        ->where('entity_id', Entity::query()->where('name', 'contents')->where('type', EntityType::CONTENTS)->value('id'))
        ->firstOrFail();
    $secondaryPresettable = Presettable::query()->where('entity_id', $secondaryEntity->id)->firstOrFail();

    $model = Modules\CMS\Models\Content::class;
    $entities = $model::fetchAvailableEntities(EntityType::CONTENTS);
    $cache_key = 'filament_cms_tabs_' . $model . '_' . $entities->pluck('id')->sort()->values()->implode(',');
    Cache::put($cache_key, [
        'all' => 2,
        (int) $primaryPresettable->entity_id => 1,
        (int) $secondaryPresettable->entity_id => 1,
    ], config('core.filament.tabs_counts_ttl_seconds'));

    $tabs = (new CMSHasRecordsTraitHarness)->getTabs();

    expect($tabs)->not->toBeEmpty()
        ->and($tabs)->toHaveKey('all');
});

it('returns an empty tab list when fewer than two entities are available', function (): void {
    setupCMSEntities([EntityType::CONTENTS]);

    $tabs = (new CMSHasRecordsTraitHarness)->getTabs();

    expect($tabs)->toBeArray()->toBeEmpty();
});

it('returns no tabs when the resource model does not use dynamic contents', function (): void {
    $tabs = (new CMSHasRecordsEntityHarness)->getTabs();

    expect($tabs)->toBeArray()->toBeEmpty();
});
