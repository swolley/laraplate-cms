<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Models\Entity;
use Modules\Cms\Tests\TestCase;
use Modules\Cms\Tests\Unit\Filament\Utils\CmsHasRecordsEntityHarness;
use Modules\Cms\Tests\Unit\Filament\Utils\CmsHasRecordsTraitHarness;
use Modules\Core\Models\Pivot\Presettable;
use Modules\Core\Models\Preset;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (class_exists(Modules\Core\Services\DynamicContentsService::class)) {
        Modules\Core\Services\DynamicContentsService::reset();
    }

    Cache::flush();
});

function insertMinimalContentRow(int $entity_id, int $presettable_id): void
{
    $now = now();
    $content_id = DB::table('contents')->insertGetId([
        'entity_id' => $entity_id,
        'presettable_id' => $presettable_id,
        'order_column' => 0,
        'lock_version' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('contents_translations')->insert([
        'content_id' => $content_id,
        'locale' => 'en',
        'title' => 'Tab coverage ' . $content_id,
        'slug' => 'tab-coverage-' . $content_id,
        'components' => json_encode([], JSON_THROW_ON_ERROR),
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

function createSecondContentsEntityWithPreset(): Entity
{
    $primaryPreset = Preset::query()
        ->where('entity_id', Entity::query()->where('name', 'contents')->where('type', EntityType::CONTENTS)->value('id'))
        ->firstOrFail();

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

    foreach ($primaryPreset->fields as $field) {
        if (! $secondaryPreset->fields()->where('fields.id', $field->id)->exists()) {
            $secondaryPreset->fields()->attach($field->id, [
                'default' => $field->pivot->default,
                'is_required' => (bool) $field->pivot->is_required,
            ]);
        }
    }

    Presettable::query()->firstOrCreate(
        ['entity_id' => $secondary->id, 'preset_id' => $secondaryPreset->id],
        ['entity_id' => $secondary->id, 'preset_id' => $secondaryPreset->id],
    );

    return $secondary;
}

it('returns filament tabs with badges when multiple content entities exist and counts are present', function (): void {
    setupCmsEntities([EntityType::CONTENTS]);

    $secondaryEntity = createSecondContentsEntityWithPreset();

    if (class_exists(Modules\Core\Services\DynamicContentsService::class)) {
        Modules\Core\Services\DynamicContentsService::reset();
    }

    $primaryPresettable = Presettable::query()
        ->where('entity_id', Entity::query()->where('name', 'contents')->where('type', EntityType::CONTENTS)->value('id'))
        ->firstOrFail();
    $secondaryPresettable = Presettable::query()->where('entity_id', $secondaryEntity->id)->firstOrFail();

    insertMinimalContentRow((int) $primaryPresettable->entity_id, (int) $primaryPresettable->id);
    insertMinimalContentRow((int) $secondaryEntity->id, (int) $secondaryPresettable->id);

    $tabs = (new CmsHasRecordsTraitHarness)->getTabs();

    expect($tabs)->not->toBeEmpty()
        ->and($tabs)->toHaveKey('all');
});

it('returns an empty tab list when fewer than two entities are available', function (): void {
    setupCmsEntities([EntityType::CONTENTS]);

    $tabs = (new CmsHasRecordsTraitHarness)->getTabs();

    expect($tabs)->toBeArray()->toBeEmpty();
});

it('returns no tabs when the resource model does not use dynamic contents', function (): void {
    $tabs = (new CmsHasRecordsEntityHarness)->getTabs();

    expect($tabs)->toBeArray()->toBeEmpty();
});
