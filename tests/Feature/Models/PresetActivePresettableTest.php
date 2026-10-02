<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Pivot\Presettable;
use Modules\CMS\Models\Preset;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Services\PresetVersioningService;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCMSEntities([EntityType::Contents]);
    $this->preset = Preset::query()->where('name', 'default')->firstOrFail();
});

it('returns the preset\'s own module presettable, not Core\'s abstract one', function (): void {
    $active = $this->preset->activePresettable();

    expect($active)->toBeInstanceOf(Presettable::class)
        ->and($active->preset_id)->toBe($this->preset->id)
        ->and($active->entity_id)->toBe($this->preset->entity_id);
});

it('returns the latest version once a new one is created', function (): void {
    $previous = $this->preset->activePresettable();

    $latest = resolve(PresetVersioningService::class)->createVersion($this->preset);

    expect($this->preset->activePresettable()->getKey())->toBe($latest->getKey())
        ->and($latest->version)->toBeGreaterThan($previous->version);
});
