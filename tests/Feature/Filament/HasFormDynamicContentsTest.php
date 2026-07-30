<?php

declare(strict_types=1);

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Tests\Stubs\Filament\HasFormHarness;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCMSEntities([EntityType::Contents]);
});

it('injects entity preset cascade fields for HasDynamicContents models', function (): void {
    $schema = HasFormHarness::run(
        Schema::make()
            ->model(Content::class)
            ->components([
                TextInput::make('title'),
            ]),
    );

    $components = $schema->getComponents(withHidden: true);
    $by_name = [];

    foreach ($components as $component) {
        if (method_exists($component, 'getName')) {
            $by_name[$component->getName()] = $component;
        }
    }

    expect($by_name)->toHaveKeys(['dynamic_entity_id', 'dynamic_preset_id', 'presettable_id', 'title'])
        ->and($by_name['dynamic_entity_id'])->toBeInstanceOf(Select::class)
        ->and($by_name['dynamic_preset_id'])->toBeInstanceOf(Select::class)
        ->and($by_name['presettable_id'])->toBeInstanceOf(Hidden::class)
        ->and($by_name['dynamic_entity_id']->isDehydrated())->toBeFalse()
        ->and($by_name['dynamic_preset_id']->isDehydrated())->toBeFalse()
        ->and($by_name['presettable_id']->isDehydrated())->toBeTrue();
});

it('leaves non-dynamic body fields after the cascade prefix', function (): void {
    $schema = HasFormHarness::run(
        Schema::make()
            ->model(Content::class)
            ->components([
                TextInput::make('title'),
            ]),
    );

    $names = array_values(array_map(
        static fn ($component): ?string => method_exists($component, 'getName') ? $component->getName() : null,
        $schema->getComponents(withHidden: true),
    ));

    // Content uses HasOptimisticLocking, so the hidden guard is appended last.
    expect($names)->toBe(['dynamic_entity_id', 'dynamic_preset_id', 'presettable_id', 'title', 'lock_version']);
});

it('strips Filament-generated entity_id and presettable_id to avoid duplicates', function (): void {
    $schema = HasFormHarness::run(
        Schema::make()
            ->model(Content::class)
            ->components([
                Select::make('entity_id')->relationship('entity', 'name'),
                Select::make('presettable_id')->relationship('presettable', 'name'),
                TextInput::make('title'),
            ]),
    );

    $names = array_values(array_map(
        static fn ($component): ?string => method_exists($component, 'getName') ? $component->getName() : null,
        $schema->getComponents(withHidden: true),
    ));

    expect($names)->toBe(['dynamic_entity_id', 'dynamic_preset_id', 'presettable_id', 'title', 'lock_version'])
        ->and($names)->not->toContain('entity_id');
});
